(function ($) {
  $(document).on("ajax-cart-after-item-rendered[iq-commerce-ajax-cart-item]", function (e, args) {

    // delete item from cart
    args.item.find('[data-remove-item]').click(function(){
      Drupal.behaviors.iq_commerce_ajax_cart.getCsrfToken(function (csrfToken) {
        Drupal.behaviors.iq_commerce_ajax_cart.removeFromCart(csrfToken, args.order.order_id, args.order.order_item_id);
      });
    });

    // manually change item quantity
    args.item.find('[data-item-quantity]').change(function(){
      var amount = parseInt($(this).val());
      Drupal.behaviors.iq_commerce_ajax_cart.getCsrfToken(function (csrfToken) {
        Drupal.behaviors.iq_commerce_ajax_cart.updateItem(csrfToken, args.order.order_id, args.order.order_item_id, amount );
      });
    });

    // increase/decrease item quantity
    args.item.find('[data-increase-item-quantity]').click(function(){
      let val = parseInt($(this).siblings('[data-item-quantity]').val());
      let amount = parseInt($(this).data('increase-item-quantity'));
      $(this).siblings('[data-item-quantity]').val(Math.max(0,val + amount )).change();
    });


  });

})(jQuery);
