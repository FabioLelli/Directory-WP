jQuery(document).ready(function(){

  scheda = document.querySelector("#scheda");
  document.addEventListener('click', function (event) {
  if (!event.target.matches('.anno')) return;
    event.preventDefault();
    url = event.target.href;
    anno = event.target.innerHTML;
    scheda.src = url;
    scheda.title = anno;
  }, false);

  jQuery.ajax({
        url: data.ajax_url,
        type: "GET",
        data: {
          action : 'fl_directory_xhr',
          page : "1",
          search : "",
          directory : data.directory,
          perPage : jQuery('#numero-risultati').val(),
          estensione : jQuery('#estensione').val(),
          estensioni : jQuery('#estensioni').val()
        },
        success: function(data)
        {
          dati = JSON.parse(data);
            jQuery("#pagination").html(dati.pagination);
            jQuery("#lista").html(dati.elenco);
        }       
   });

  jQuery(document).on("click", ".page", function(event){
    event.preventDefault();
      jQuery.ajax({
              url: data.ajax_url,
              type: "GET",
              data: {
                action : 'fl_directory_xhr',
                page : jQuery(this).attr('data-page'),
                search : jQuery('#search').val(),
                directory : data.directory,
                perPage : jQuery('#numero-risultati').val(),
                estensione : jQuery('#estensione').val(),
                estensioni : jQuery('#estensioni').val()
              },
              success: function(data)
              {
                dati = JSON.parse(data);
                  jQuery("#pagination").html(dati.pagination);
                  jQuery("#lista").html(dati.elenco);
                  jQuery('#page').val();
              }       
         });
  });

  jQuery(document).on("change", "#numero-risultati", function(){
      jQuery.ajax({
              url: data.ajax_url,
              type: "GET",
              data: {
                action : 'fl_directory_xhr',
                page : '1',
                search : jQuery('#search').val(),
                directory : data.directory,
                perPage : jQuery('#numero-risultati').val(),
                estensione : jQuery('#estensione').val(),
                estensioni : jQuery('#estensioni').val()
              },
              success: function(data)
              {
                dati = JSON.parse(data);
                  jQuery("#pagination").html(dati.pagination);
                  jQuery("#lista").html(dati.elenco);

              }       
         });
  });

  jQuery(document).on("keyup", "#search", function(){
      jQuery.ajax({
              url: data.ajax_url,
              type: "GET",
              data: {
                page : '1',
                action : 'fl_directory_xhr',
                search : jQuery('#search').val(),
                directory : data.directory,
                perPage : jQuery('#numero-risultati').val(),
                estensione : jQuery('#estensione').val(),
                estensioni : jQuery('#estensioni').val()
              },
              success: function(data)
              {
                dati = JSON.parse(data);
                  jQuery("#pagination").html(dati.pagination);
                  jQuery("#lista").html(dati.elenco);
              }       
         });
  });

});