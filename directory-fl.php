<?php

/*
 * Plugin Name:       Directory per Wordpress
 * Description:       Elencare i file di una cartella con visualizzatore e link per scaricarli
 * Author:            Fabio Lelli
 * License:           GPL v3
 */


function directory_shortcodes_init() {
    add_shortcode( 'directory', 'directory_fl_shortcode' );
    add_action('wp_enqueue_scripts', 'directory_fl_add_assets');
    add_action( 'wp_ajax_fl_directory_xhr', 'fl_ajax_directory_xhr' );
    add_action( 'wp_ajax_nopriv_fl_directory_xhr', 'fl_ajax_directory_xhr' );
    add_action( 'wp_ajax_fl_directory_xhr_primo', 'fl_ajax_directory_xhr_primo' );
    add_action( 'wp_ajax_nopriv_fl_directory_xhr_primo', 'fl_ajax_directory_xhr_primo' );
}

add_action( 'init', 'directory_shortcodes_init' );
add_action('init', 'directory_fl_custom_post_type');


function directory_fl_custom_post_type() {
    register_post_type('fl_directory',
        array(
            'supports'     => array('title'),
            'labels'      => array(
                'name'          => __( 'Directory', 'textdomain' ),
                'singular_name' => __( 'Directory', 'textdomain' ),
            ),
            'public'      => true,
            'has_archive' => true,
        )
    );
}

add_action('admin_init', 'fl_directory_add_meta_boxes');

function fl_directory_add_meta_boxes() {
    add_meta_box( 'fl_dati_elenco', 'Dati elenco', 'dati_elenco_fl_meta_box_display', 'fl_directory', 'normal', 'default');
}

function dati_elenco_fl_meta_box_display () {
    global $post;
    $dati_elenco = get_post_meta($post->ID, 'fl_dati_elenco',true);
    wp_nonce_field( 'fl_dati_elenco_meta_box_nonce', 'fl_dati_elenco_meta_box_nonce' );
    if ($dati_elenco) { ?>
        <table id="dati_elenco" width="100%">
        <tr>
            <td>
                <label>Directory (relativa a wp-content) </label> <input type="text" name="directory" placeholder="Directory" value="<?php if($dati_elenco['directory'] != '') echo esc_attr( $dati_elenco['directory'] ); ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label>Estensioni da visualizzare. Separare con punto e virgola o lasciare vuoto per tutti i file </label><input type="text" name="estensioni" placeholder="Estensioni" value="<?php if($dati_elenco['estensioni'] != '') echo esc_attr( $dati_elenco['estensioni'] ); ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label>Includi visualizzatore nella pagina con un iframe </label><input name="visualizzatore" type="checkbox" value="1" <?php checked("1", $dati_elenco['visualizzatore']); ?>/>
            </td>
        </tr>
        <tr>
            <td>
                <label>Altezza del visualizzatore</label><input name="altezza" type="number" value="<?php if($dati_elenco['altezza'] != '') echo esc_attr( $dati_elenco['altezza'] ); ?>"/>
            </td>
        </tr>
        <tr>
            <td>
                <label>Larghezza del visualizzatore</label><input name="larghezza" type="number" value="<?php if($dati_elenco['larghezza'] != '') echo esc_attr( $dati_elenco['larghezza'] ); ?>"/>
            </td>
        </tr>
        <tr>
            <td>
                <label>Mostra estensione </label><input name="estensione" type="checkbox" value="1" <?php checked("1", $dati_elenco['estensione']); ?>/>
            </td>
        </tr>
        <tr>
            <td>
                <label>Includi campo di ricerca </label><input name="ricerca" type="checkbox" value="1" <?php checked("1", $dati_elenco['ricerca']); ?>/>
            </td>
        </tr>
        </table>
    <?php } else { ?>
            <table id="dati_elenco" width="100%">
        <tr>
            <td>
                <label>Directory (relativa a wp-content)</label> <input type="text" name="directory" placeholder="Directory" value="">
            </td>
        </tr>
        <tr>
            <td>
                <label>Estensioni da visualizzare (separare con punto e virgola o lasciare vuoto per tutti i file)</label><input type="text" name="estensioni" placeholder="Estensioni" value="">
            </td>
        </tr>
        <tr>
            <td>
                <label>Includi visualizzatore nella pagina con un iframe</label><input name="visualizzatore" type="checkbox" value="1"/>
            </td>
        </tr>
        <tr>
            <td>
                <label>Altezza del visualizzatore</label><input name="altezza" type="number" value="600"/>
            </td>
        </tr>
        <tr>
            <td>
                <label>Larghezza del visualizzatore</label><input name="larghezza" type="number" value="800"/>
            </td>
        </tr>
        <tr>
            <td>
                <label>Mostra estensione </label><input name="estensione" type="checkbox" value="1" />
            </td>
        </tr>
        <tr>
            <td>
                <label>Includi campo di ricerca </label><input name="ricerca" type="checkbox" value="1" />
            </td>
        </tr>
    </table>
    <?php }

    if ($post->ID && get_post_status($post->ID) == 'publish') {
    echo "<table><tr><td>Utilizza questo codice:<code>[directory id='" . $post->ID . "'][/directory]</code></tr></td></table>";
    }

}

add_action('save_post', 'fl_dati_elenco_meta_box_save');

function fl_dati_elenco_meta_box_save($post_id) {
    if ( ! isset( $_POST['fl_dati_elenco_meta_box_nonce'] ) ||
    ! wp_verify_nonce( $_POST['fl_dati_elenco_meta_box_nonce'], 'fl_dati_elenco_meta_box_nonce' ) )
        return;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    if (!current_user_can('edit_post', $post_id))
        return;

    $old = get_post_meta($post_id, 'fl_dati_elenco', true);

    $new = array(
        'directory' => $_POST['directory'],
        'estensioni' => $_POST['estensioni'],
        'visualizzatore' => $_POST['visualizzatore'],
        'estensione' => $_POST['estensione'],
        'altezza' => $_POST['altezza'],
        'larghezza' => $_POST['larghezza'],
        'ricerca' => $_POST['ricerca']
    );

    if ( !empty( $new ) && $new != $old )
        update_post_meta( $post_id, 'fl_dati_elenco', $new );
    elseif ( empty($new) && $old )
        delete_post_meta( $post_id, 'fl_dati_elenco', $old );
}



function directory_fl_add_assets () {
    wp_enqueue_script('custom_directory_fl', plugin_dir_url( __DIR__ ) . 'directory-fl/public/js/app.js', array('jquery'), false, true);
    wp_enqueue_style('custom_directory_fl_css', plugin_dir_url( __DIR__ ) . 'directory-fl/public/css/custom_fl.css');
}


function fl_ajax_directory_xhr_primo () {
    $directory = $_GET['directory'];
    $estensioni = explode(";", $_GET['estensioni']);

    $interoElenco = array();

    $dir = WP_CONTENT_DIR . "/" . $directory;

    $elenco = scandir($dir);
    foreach ($elenco as $file) {
        if ($file != "." && $file != "..") {
            if (count($estensioni) == 0) {
                $interoElenco[] = $file; 
            } else {
                $punto = strrpos($file,".");
                $esten = substr($file,$punto+1);
                if ($punto && in_array($esten, $estensioni)) {
                    $interoElenco[] = $file; 
                }
            }
        } 
    }

    natcasesort($interoElenco);

    $primo = $interoElenco[0];
    $nomeFile = pathinfo($primo, PATHINFO_FILENAME);


    $data = array (
        'src' => content_url() . "/$directory/$primo",
        'anno' => $nomeFile
    );

    echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    wp_die();

}



function fl_ajax_directory_xhr () {

    $directory = $_GET['directory'];
    $pagina = $_GET['page'];
    $ricerca = ($_GET['search']);
    $perPage = $_GET['perPage'];
    $estensione = $_GET['estensione'];
    $estensioni = explode(";", $_GET['estensioni']);

    if ($pagina == 0) {$pagina = 1;}

    $data = array();
    $interoElenco = array();

    $dir = WP_CONTENT_DIR . "/" . $directory;

    $elenco = scandir($dir);
    foreach ($elenco as $file) {
        if ($file != "." && $file != "..") {
            if (count($estensioni) == 0) {
                $interoElenco[] = $file; 
            } else {
                $punto = strrpos($file,".");
                $esten = substr($file,$punto+1);
                if ($punto && in_array($esten, $estensioni)) {
                    $interoElenco[] = $file; 
                }
            }
        } 
    }

    natcasesort($interoElenco);

    if ($ricerca && $ricerca !== "") {
        $elencofiltrato = array();
        foreach ($interoElenco as $voce) {
            if (strpos($voce, strval($ricerca)) !== false) {
                $elencofiltrato[] = $voce;
            }
        }
    } else {
        $elencofiltrato = $interoElenco;
    }

    $length = count($elencofiltrato);

    if ($perPage == "tutti") {
        $perPage = $length;
    } else {
        $perPage = $perPage;
    }

    $numeroPagine = ceil($length/$perPage);
    $offset = (strval($pagina)-1) * $perPage;
    $elencoVisibile = array_slice($elencofiltrato, $offset, $perPage); 

    $elencoHtml = "";
    foreach ($elencoVisibile as $file) {
        $nomeFile = pathinfo($file, PATHINFO_FILENAME);
        if ($estensione == "1") {
            $elencoHtml .="<li><a href='". content_url() . "/$directory/$file' target='_blank' class='anno'>$file</a></li>";
        } else {
            $elencoHtml .="<li><a href='". content_url() . "/$directory/$file' target='_blank' class='anno'>$nomeFile</a></li>";
        }
    }

    $data['elenco'] = $elencoHtml;

    $impaginazioneHtml = "";

    if ($numeroPagine > 1) {

        if ($pagina == 1) {$precedente = 1;} else { $precedente = strval($pagina - 1);}

        $impaginazioneHtml .= "<li><a href='#' class='page' data-page='1'>&laquo;&laquo;</a></li><li><a href='#' data-page='" . $precedente . "' class='page'>&laquo;</a></li>";

        $impaginazioneHtml .= "<li>" . strval($pagina) . "</li>";

        if ($pagina == $numeroPagine) {$successivo = $numeroPagine; } else {$successivo = strval($pagina + 1);}

        $impaginazioneHtml .= "<li><a href='#' class='page' data-page='" . $successivo . "'>&raquo;</a></li><li><a href='#' class='page' data-page='". strval($numeroPagine) . "'>&raquo;&raquo;</a></li>";
    }

    $data['pagination'] = $impaginazioneHtml;

    echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    wp_die();
}


/**
 * Lo shortcode
 *
 * @param array  $atts    Shortcode attributes. Default empty.
 * @param string $content Shortcode content. Default null.
 * @param string $tag     Shortcode tag (name). Default empty.
 * @return string Shortcode output.
 */
function directory_fl_shortcode( $atts = [], $content = null, $tag = '' ) {
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $directory_fl_atts = shortcode_atts(
        array(
            'id' => ""
        ), $atts, $tag
    );

    $dati_elenco = get_post_meta($directory_fl_atts['id'], 'fl_dati_elenco', true);

    wp_enqueue_script('custom_directory_fl', plugin_dir_url( __DIR__ ) . 'directory-fl/public/js/app.js', array('jquery'), false, true);
    wp_localize_script('custom_directory_fl', 'data', array(
                                                            "estensione" => $dati_elenco['estensione'],
                                                            "estensioni" => $dati_elenco['estensioni'],
                                                            "directory" => $dati_elenco['directory'],
                                                            'ajax_url' => admin_url( 'admin-ajax.php' )
                                                        )
    );
    wp_enqueue_style('custom_directory_fl_css', plugin_dir_url( __DIR__ ) . 'directory-fl/public/css/custom_fl.css');
    
    $o = '
        <div id="container-fl-list">
        <div id="listId">';
        if ($dati_elenco['ricerca']) {
            $o .= '<input class="search" placeholder="Cerca" id="search" name="search"/>';
        }

        $o .= "<select name='numero-risultati' id='numero-risultati'>
                    <option value='20'>20 risultati</option>
                    <option value='50'>50 risultati</option>
                    <option value='100'>100 risultati</option>
                    <option value='tutti'>Mostra tutti</option>
                </select>";

        $o .= "<input type='hidden' name='estensioni' id='estensioni' value='" . $dati_elenco['estensioni'] . "' /><input type='hidden' name='estensione' id='estensione' value='" . $dati_elenco['estensione'] . "' />";
        $o .= "<ul class='list' id='lista'></ul>";
        $o .= '<ul class="pagination" id="pagination"></ul></div>'; 

        if ($dati_elenco['visualizzatore'] == "1") {
        $o .= '<div id="pdfView">
            <iframe id="scheda" src="" width="' . $dati_elenco['larghezza'] . '" height="' . $dati_elenco['altezza'] . '" loading="lazy" title=""></iframe>
        </div>';
        }

        $o .= "</div>";
    
    return $o;
} ?>