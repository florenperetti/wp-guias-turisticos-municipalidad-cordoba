(function(window, document, $) {

  const $GTM = $('#GTM');
  const $form = $GTM.find('form');
  const $reset = $GTM.find('#filtros__reset');
  const $html = $('html,body');
  let $resultados = $GTM.find('.resultados');
  let $resultadosContainer = $resultados.find('.resultado__container');
  let $gifCarga = $resultados.find('.cargando');
  let $paginacion = $resultados.find('.paginacion');

  $reset.click(function(e) {
    e.preventDefault();
    $form[0].reset();
    $form.submit();
  });

  const iniciarCarga = () => {
    $html.animate({scrollTop: $GTM.offset().top-100},'slow');
    $resultadosContainer.hide();
    $paginacion.hide();
    $gifCarga.show();
  }

  const referenciar = () => {
    $resultados = $('#GTM .resultados');
    $resultadosContainer = $resultados.find('.resultado__container');
    $gifCarga = $resultados.find('.cargando');
    $paginacion = $resultados.find('.paginacion');
  }

  $form.submit(function(e) {
    e.preventDefault();
    const datos = $form.serializeArray();
    iniciarCarga();
    $.ajax({
      type: "POST",
      dataType: "JSON",
      url: buscarGuiasTuristicos.url,
      data: {
        action: 'buscar_guias_turisticos',
        nonce: buscarGuiasTuristicos.nonce,
        idioma_id: datos[0].value
      },
      success: function(response) {
        if (response.data) {
          $resultados.html(response.data);
          referenciar();
        }
      }
    });
  });

  $(document).on('click','#GTM .paginacion__boton', function(e) {
    const pagina = $(this).data('pagina');
    const $boton = $(e.target);
    const texto = $boton.html();
    $boton.html('...');
    const datos = $form.serializeArray()
    iniciarCarga();
    $.ajax({
      type: "POST",
      dataType: "JSON",
      url: buscarGuiasTuristicos.url,
      data: {
        action: 'buscar_guias_turisticos_pagina',
        nonce: buscarGuiasTuristicos.nonce,
        pagina: pagina,
        idioma_id: datos[0].value
      },
      success: function(response) {
        if (response.data) {
          $resultados.html(response.data);
          referenciar();
          $('body').animate({scrollTop: 50}, 1000);
        }
      },
      done: function() {
        $boton.html(texto);
      }
    });
  });
})(window, document, jQuery);