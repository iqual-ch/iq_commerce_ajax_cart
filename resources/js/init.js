(function ($) {

  Drupal.behaviors.iq_commerce_ajax_cart_init_forms = {
    attach: function (context, settings) {

      // bind cart update to forms - product
      $(once('add-to-cart-form-init', 'form.commerce-order-item-add-to-cart-form', context)).each(function () {
        $(this).on('click', '.form-submit', function (e) {
          $(this).parents('form').data('button-clicked', 'cart');
        });
        $(this).on('submit', function (e) {
          e.preventDefault();

          let variationId = $(this).attr('action').split('v=')[1];
          let quantity = parseInt($(this).find('input[name*="quantity"]').val());
          if (!variationId) {
            let attributes = $(this).serializeArray().reduce(function (accumulator, item) {
              if (/purchased_entity\[0\]\[(.*)\]\[(.*)\]/g.exec(item.name) && /purchased_entity\[0\]\[(.*)\]\[(.*)\]/g.exec(item.name)[1] == "attributes") {
                accumulator[/purchased_entity\[0\]\[attributes]\[(.*)\]/g.exec(item.name)[1]] = item.value
              }
              return accumulator;
            }, []);

            let mapping = drupalSettings.add_to_ajax_cart.mapping_varation_attributes;

            variationId = parseInt(Object.keys(mapping).filter(function (key) {
              let found = true;
              Object.keys(attributes).forEach(function (attribute) {
                if (mapping[key][attribute] != attributes[attribute]) {
                  found = false;
                }
              })
              return found;
            })[0]);
          }

          variationId = parseInt(variationId);

          var formData = {};
          $(this).serializeArray().forEach(function(field){
            if (field.name.startsWith('field_')) {
              formData[field.name] = field.value
            }
          });
          Drupal.behaviors.iq_commerce_ajax_cart.getCsrfToken(function (csrfToken) {
            Drupal.behaviors.iq_commerce_ajax_cart.addToCart(csrfToken, 'commerce_product_variation', variationId, quantity, null, formData);
          });
        });
      });



    }
  };




  $(document).on("iq-commerce-cart-init", function (e) {

    // load cart on page load
    Drupal.behaviors.iq_commerce_ajax_cart.refreshCart('[data-mini-cart-content]', {
      showCart: false,
    });

    // bind cart update to forms
    $(once('add-to-cart-form-init', 'form.commerce-variation-add-to-cart-form', document)).each(function () {
      $(this).on('click', '.form-submit', function (e) {
        $(this).parents('form').data('button-clicked', 'cart');
      });
      $(this).on('submit', function (e) {
        let trigger = $(this).find('button')[0];
        e.preventDefault();
        var orderProductData = $(this).serializeArray().reduce(function (obj, item) {
          obj[item.name] = item.value;
          return obj;
        }, {});
        var formData = {};
        $(this).serializeArray().forEach(function(field){
          if (field.name.startsWith('field_')) {
            formData[field.name] = field.value
          }
        });
        Drupal.behaviors.iq_commerce_ajax_cart.getCsrfToken(function (csrfToken) {
          Drupal.behaviors.iq_commerce_ajax_cart.addToCart(csrfToken, 'commerce_product_variation', parseInt(orderProductData['variation_id']), parseInt(orderProductData['quantity']), trigger, formData);
        });
      });
    });
  });

  $(document).on("iq-commerce-cart-add-before", function (e, orderData) {
    if (orderData.trigger) {
      $(orderData.trigger).addClass('laoding');
    }
  });

  $(document).on("iq-commerce-cart-add-after", function (e, orderData) {
    if (orderData.trigger) {
      $(orderData.trigger).removeClass('laoding');
      $(orderData.trigger).addClass('success');
      setTimeout(function(){
        $(orderData.trigger).removeClass('success');
      }, 5000);
    }
    Drupal.behaviors.iq_commerce_ajax_cart.refreshCart('[data-mini-cart-content]', {
      showCart: true,
    });
  });

  $(document).on("iq-commerce-cart-refresh-after", function (e, updateData) {
    if (updateData.additionalData.showCart) {
      $('.iq-commerce-mini-cart').addClass('show')
    }

    let totalQuantity = 0

    if (updateData.cartData[0] && updateData.cartData[0].order_items && updateData.cartData[0].order_items.length) {
      totalQuantity = updateData.cartData[0].order_items.map(function (item) {
        return Math.round(item.quantity)
      }).reduce(function (a, b) {
        return a + b;
      });
    }

    if (totalQuantity) {
      $('.iq-commerce-mini-cart .count').text(totalQuantity);
    }else{
      $('.iq-commerce-mini-cart .count').text('');
    }
  });

  $(document).on("iq-commerce-cart-remove-after", function (e, orderData) {
    Drupal.behaviors.iq_commerce_ajax_cart.refreshCart('[data-mini-cart-content]', {
      showCart: true,
    });
  });

  $(document).on("iq-commerce-cart-update-after", function (e, orderData) {
    Drupal.behaviors.iq_commerce_ajax_cart.refreshCart('[data-mini-cart-content]', {
      showCart: true,
    });
  });


  $('.iq-commerce-mini-cart').mouseout(function () {
    $(this).removeClass('show')
  })

  $(document).on("iq-commerce-cart-update-after", function (e, orderData) {
    Drupal.behaviors.iq_commerce_ajax_cart.refreshCart('[data-mini-cart-content]', {
      showCart: true,
    });
  });




})(jQuery);
