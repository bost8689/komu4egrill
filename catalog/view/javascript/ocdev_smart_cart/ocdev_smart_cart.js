$(function() {
  $('#cart > button').removeAttr('data-toggle').attr('onclick', 'getOCwizardModal_smca(false,\'' + 'load' + '\');');
});

function buttonManipulate() {
  $.ajax({
    type: 'get',
    url:  'index.php?route=extension/module/ocdev_smart_cart/cartProducts',
    dataType: 'json',
    cache: false,
    success: function(json) {
      if (json['error']) {
        $('#smca-modal-body > .modal-footer > input.go-button-bottom, #smca-modal-body > .modal-footer > input.save-button-bottom, #smca-ajax-products').remove();
        $('#smca-modal-data').html('<div id="smca-modal-data-empty">' + json['error'] + '</div>');
      }
      if (json['cart_products']) {
        $.each(json['cart_products'], function(i, value) {
          $('[onclick="getOCwizardModal_smca(\'' + value + '\',\'' + 'add' + '\');"]')
          .html('<i class="fa fa-shopping-cart"></i> <span class="hidden-xs hidden-sm hidden-md">' + json['text_in_cart'] + '</span>')
          .attr('onclick', 'getOCwizardModal_smca(\'' + value + '\',\'' + 'load' + '\');');
          $('[onclick="getOCwizardModal_smca(\'' + value + '\',\'' + 'add_option' + '\');"]')
          .html(json['text_in_cart'])
          .attr('onclick', 'getOCwizardModal_smca(\'' + value + '\',\'' + 'load_option' + '\');');
        });
      }
      if (json['cart_products_vs_options']) {
        $.each(json['cart_products_vs_options'], function(i, value) {
          $('[onclick="getOCwizardModal_smca(\'' + value + '\',\'' + 'add' + '\');"]')
          .html('<i class="fa fa-shopping-cart"></i> <span class="hidden-xs hidden-sm hidden-md">' + json['text_in_cart'] + '</span>');
          $('[onclick="getOCwizardModal_smca(\'' + value + '\',\'' + 'add_option' + '\');"]')
          .html(json['text_in_cart_vs_options']);
        });
      }
      $('#cart-total').html(json['total']);
    }
  });
}
