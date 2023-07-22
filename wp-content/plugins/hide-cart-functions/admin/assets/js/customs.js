/**
 * All of the code for your admin-facing JavaScript source
 * should reside in this file.
 *
 * Note: It has been assumed you will write jQuery code here, so the
 * $ function reference has been prepared for usage within the scope
 * of this function.
 *
 * jQuery(document).ready( function($){
 *  // Add jQuery or vanilla javascript code here
 * });
 * 
 */

jQuery(document).ready(function ($) {
  // -------------------------------------------------------------
  //   Initialize multi select
  // -------------------------------------------------------------

  if ($('.hwcf_categories').length > 0) {
    var multiSelect = new IconicMultiSelect({
      select: ".hwcf_categories",
      placeholder: hwcf.search_text,
      noResults: hwcf.search_none,
    });
    multiSelect.init();
    multiSelect.subscribe(function (e) {
      var selected_options = e.selection;
      var selected_option = [];
      $(".hwcf_categories option:selected").removeAttr("selected");
      selected_options.forEach(function (item) {
        selected_option.push(item.value);
      });
      $("#hwcf_categories").val(selected_option);

    });
  }
  $(document).on("click", "#pcfw_notice button.notice-dismiss", function (e) {
    e.preventDefault();
    hwcf_dismiss_notice(0)
  })

  $(document).on("click", "a.pcfw-feedback", function (e) {
    e.preventDefault();
    hwcf_dismiss_notice(1)
    $("#pcfw_notice").slideUp();
    window, open($(this).attr("href"), "_blank");
  })

  // Disable User Role selection if rules applied to guest only
  $(document).on("change", "#hwcf_loggedinUsers", function (e) {

    if (this.checked) {
      $(document).find("#hwcf_userType").removeAttr("disabled");
    } else {
      $(document).find("#hwcf_userType").attr("disabled", "disabled");
    }

  });

  $(document).on("change", "#hwcf_hide_price", function (e) {

    if (!this.checked) {
      $(document).find("#hwcf_overridePriceTag").removeAttr("disabled");
    } else {
      $(document).find("#hwcf_overridePriceTag").attr("disabled", "disabled");
    }

  });

  function hwcf_dismiss_notice(is_final) {

    $.ajax({
      url: ajaxurl,
      data: { action: "pcfw_dismiss_notice", "dismissed_final": is_final },
      type: "POST",
      dataType: "json",
      beforeSend: function (res) {
        console.log("Dismiss Final: ", is_final);
      },
      success: function (response) {
        console.log(response)
      }
    })
  }

});

// wocommerce search product query

jQuery(document).ready(($) => {

  
  // stop selecting both checkboxes at same time starts here 
  jQuery('.guest-checkbox').on('change', function(){
    if (jQuery(this).is(":checked") )
      jQuery('.logged-in-checkbox').prop('checked', false);
  });
  jQuery('.logged-in-checkbox').on('change', function(){
    if (jQuery(this).is(":checked") )
      jQuery('.guest-checkbox').prop('checked', false);
  });
  // stop selecting both checkboxes at same time ends here 



  jQuery('#custom-product-search-field').select2({
    placeholder: "Search Product",
    minimumInputLength: 3,
    allowClear: true,
    ajax: {
      delay: 250,
      type: 'post',
      dataType: 'json',
      url: ajaxurl,
      data: function (params) {
        return {
          action: 'custom_product_search',
          product_name: params.term
        }
      },
      processResults: function (data) {
        return {
          results: $.map(data, function (item) {
            return {
              text: item.post_name,
              id: item.ID,
            }
          })
        };
      },
      cache: true
    }
  }).on("select2:unselect", function (e) { 
    product_id = e.params.data.id;
    const pro_elem = jQuery("#hwcf_products");
    var product_ids = pro_elem.val();
    product_ids = product_ids.split(",");
    var find_ID = product_ids.indexOf(product_id);
    if (find_ID > -1 )
      product_ids.splice( find_ID , 1 );

    product_ids = product_ids.length > 1 ? product_ids.join(",") : product_ids;
    
    pro_elem.val(product_ids);

  }).on("select2:select", function (e) { 
    product_id = e.params.data.id;
    const pro_elem = jQuery("#hwcf_products");
    var product_ids = pro_elem.val();
    product_ids = product_ids.split(",");
    product_ids.push(product_id);
    console.log( product_ids.length ) ;
    var final_ids = product_ids.filter((a) => a);
    final_ids = final_ids.length > 1 ? final_ids.join(",") : final_ids;
    pro_elem.val(final_ids);
  });

});


