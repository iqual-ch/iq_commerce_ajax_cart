(function ($) {

  'use strict';

  /**
   * Behaviors.
   */
  Drupal.behaviors.iq_commerce_ajax_cart = {
    getCsrfToken: function (callback) {
      $.get(Drupal.url('session/token'))
        .done(function (data) {
          callback(data);
        });
    },
    addToCart: function (csrfToken, purchasedEntityType, purchasedEntityId, quantity, trigger = null, formData = null) {
      var orderData = {
        purchased_entity_type: purchasedEntityType,
        purchased_entity_id: purchasedEntityId,
        quantity: quantity,
        trigger: trigger,
        form_data: formData
      };
      $(document).trigger("iq-commerce-cart-add-before", [orderData]);

      // Validate cart.
      Drupal.behaviors.iq_commerce_ajax_cart.validateCartItem(orderData);

      if (orderData.valid) {
        $.ajax({
          url: Drupal.url('cart/add?_format=json'),
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          data: JSON.stringify([orderData]),
          success: function (orderData) {
            if (trigger) {
              orderData.trigger = trigger
            }
            $(document).trigger("iq-commerce-cart-add-after", [orderData]);
          }
        });
      }
    },

    updateItem: function (csrfToken, orderID, itemID, quantity) {
      var orderData = {
        quantity: quantity
      };
      $(document).trigger("iq-commerce-cart-update-before", [orderData]);

      // Validate cart.
      Drupal.behaviors.iq_commerce_ajax_cart.validateCartItem(orderData);

      if (orderData.valid) {
        $.ajax({
          url: Drupal.url('cart/' + orderID + '/items/' + itemID + '?_format=json'),
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          data: JSON.stringify(orderData),
          success: function (orderData) {
            $(document).trigger("iq-commerce-cart-update-after", [orderData]);
          }
        });
      }
    },

    validateCartItem: function (orderData) {
      orderData.valid = true;
      orderData.error = false;
      $(document).trigger("iq-commerce-cart-validate", [orderData]);
      const $form = $('form.commerce-order-item-add-to-cart-form');
      // add error message before the form-actions element
      if (orderData.error) {
        $form.find('.form-actions').before('<div class="error-messages js-form-wrapper form-group">' + orderData.error + '</div>');
      }
      else {
        $form.find('.error-messages').remove();
      }
    },

    removeFromCart: function (csrfToken, orderID, itemID) {
      var orderData = {
        orderID: orderID,
        itemID: itemID
      };
      $(document).trigger("iq-commerce-cart-remove-before", [orderData]);
      $.ajax({
        url: Drupal.url('cart/' + orderID + '/items/' + itemID + '?_format=json'),
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        success: function (orderData) {
          $(document).trigger("iq-commerce-cart-remove-after", [orderData]);
        }
      });
    },

    refreshCart: function (targetSelector, additionalData = {}) {
      $.ajax({
        url: Drupal.url('cart?_format=json'),
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        success: function (cartData) {
          // Add additionalData to eventTrigger.
          var updateData = {
            cartData: cartData,
            additionalData: additionalData
          };
          $(document).trigger("iq-commerce-cart-refresh-before", [updateData]);
          Object.keys(drupalSettings.progressive_decoupler).filter(function (key) {
            return drupalSettings.progressive_decoupler[key].type == 'iq_commerce_ajax_cart_block'
          }).forEach(function (blockID) {
            let $blockElement = $('[id^="' + blockID + '"]');
            let blockData = drupalSettings.progressive_decoupler[blockID];
            let template = Twig.twig({ data: blockData.template });
            let pattern = blockData.ui_pattern;

            if (cartData && cartData[0] && cartData[0].order_items.length) {
              let $target = $blockElement.find(targetSelector).html('');
              cartData[0].order_items.forEach(function (item) {

                let fieldMapper = new iq_progressive_decoupler_FieldMapper(item, blockData.field_mapping);
                let $item = $(template.render(fieldMapper.applyMappging()));

                $(document).trigger('ajax-cart-after-item-rendered[' + pattern + ']', {
                  item: $item,
                  order: {
                    order_id: item.order_id,
                    order_item_id: item.order_item_id,
                  },
                });
                $target.append($item);
              });

              // Adjustment of total price displayed in the Ajax Cart
              // For WS-405 Warenkorb Pop-up - Total price Softtrend ticket
              //$blockElement.find('[data-total-value]').text(cartData[0].total_price.formatted);
              const $prices = $(cartData[0].order_items);
              let totalSum = 0;
              $prices.each(function () {
                let price = parseFloat(this.total_price.number);
                totalSum += price;
              });
              $blockElement.find('[data-total-value]').text('CHF ' + totalSum.toLocaleString('de-CH', {minimumFractionDigits: 2}));

              $blockElement.find('[data-cart-content-holder]').removeClass('loading');
              $(document).trigger('ajax-cart-after-block-rendered[' + pattern + ']', $target);
            }
            else {
              $blockElement.find('[data-total-value]').text('');
              $blockElement.find('[data-cart-content-holder]').addClass('loading');
              $blockElement.find(targetSelector).html('<p class="empty">' + $blockElement.find(targetSelector).data('label-empty') + '</p>');
            }
          });

          $(document).trigger("iq-commerce-cart-refresh-after", [updateData]);
        }
      });
    },
    attach: function (context, settings) {
      $(document).trigger("iq-commerce-cart-init");
    }
  };

})(jQuery);
