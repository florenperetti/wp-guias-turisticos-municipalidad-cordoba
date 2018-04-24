(function() {
  tinymce.create('tinymce.plugins.buscguiasturcba_button', {
    init: function(ed, url) {
      ed.addCommand('buscguiasturcba_insertar_shortcode', function() {
        //selected = tinyMCE.activeEditor.selection.getContent();
        //var content = '';
        ed.insertContent( '[buscador_guias_turisticos_cba]' );
        /*
        ed.windowManager.open({
          title: 'Buscador de Guías Turísticos',
          body: [{
            type: 'textbox',
            name: 'pag',
            label: 'Cantidad de Resultados'
          }],
          onsubmit: function(e) {
            var pags = Number(e.data.pag.trim());
            ed.insertContent( '[buscador_guias_turisticos_cba' + (pags && Number.isInteger(pags) ? ' pag="'+pags+'"' : '') + ']' );
          }
        });
        tinymce.execCommand('mceInsertContent', false, content);*/
      });
      ed.addButton('buscguiasturcba_button', {title : 'Insertar buscador de Guías Turísticos', cmd : 'buscguiasturcba_insertar_shortcode', image: url.replace('/js', '') + '/images/logo-shortcode.png' });
    }
  });
  tinymce.PluginManager.add('buscguiasturcba_button', tinymce.plugins.buscguiasturcba_button);
})();