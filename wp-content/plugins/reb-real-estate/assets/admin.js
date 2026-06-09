jQuery(function ($) {
  const input = $('#reb_gallery_ids');
  $('#reb-select-gallery').on('click', function (event) {
    event.preventDefault();

    const frame = wp.media({
      title: 'Select Gallery Images',
      button: { text: 'Use selected images' },
      multiple: true,
      library: { type: 'image' }
    });

    frame.on('select', function () {
      const ids = frame.state().get('selection').map((attachment) => attachment.id);
      input.val(ids.join(','));
    });

    frame.open();
  });
});
