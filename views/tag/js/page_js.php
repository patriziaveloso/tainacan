<script>
    $(function () {
          $('.dropdown-toggle').dropdown();
          var src = $('#src').val();  
          get_link(<?php echo $term->term_id ?>);
          var stateObj = {foo: "bar"};
          history.replaceState(stateObj, "page 2", '?tag=<?php echo $term->slug ?>');
          $('#button_links_dropdown').click(function (e) {
             var posX = $(this).position().left;
            var posY = $(this).position().top;
             $('#ul_links_dropdown').css('left', posX);
             $('#ul_links_dropdown').css('top', posY+22);
             e.preventDefault();
         });
      });
    /**
     * 
     */
    function get_link(term_id){
        $.ajax({
                url: $('#src').val() + '/controllers/tag/tag_controller.php',
                type: 'POST',
                data: { 
                    operation: 'get_link_individuals', 
                    collection_id: $("#collection_id").val(),
                    term_id:term_id
                }
            }).done(function (result) {
              elem = jQuery.parseJSON(result);
              $('#link-individuals').attr('href',elem.url);
            });
    }
    
    function get_url_category(term_id){
        return $.ajax({
                url: $('#src').val() + '/controllers/category/category_controller.php',
                type: 'POST',
                data: { 
                    operation: 'get_url_category', 
                    collection_id: $("#collection_id").val(),
                    term_id:term_id
                }
        });
    }
    
</script>