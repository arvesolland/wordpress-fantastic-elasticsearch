<?php
/*
Deals widget
*/
 
class Deals_Widget extends WP_Widget
{
  function Deals_Widget()
  {
    $widget_ops = array('classname' => 'Deals_Widget', 'description' => 'Deals Widget Description');
    $this->WP_Widget('Deals_Widget', 'Deals Widget', $widget_ops);
  }
 
  function form($instance)
  {
    //$instance = wp_parse_args((array) $instance, array( 'title' => '', 'show_own_deals' => 'no', 'selected_deals' => '' ));
    $title = esc_attr($instance['title']);
    $show_own_deals = esc_attr($instance['show_own_deals']);
    $selected_deals = $instance['selected_deals'];
    $maxno = 2;
    if (isset($instance['maxno']) && $instance['maxno'] > 0) {
      $maxno = $instance['maxno'];  
    }
    $showDeals = '';
    if ($show_own_deals == 'yes') {
      $showDeals = ' style="display:none;"';
    }
    

    
    //debug($selected_deals);
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
  <p><label for="<?php echo $this->get_field_id('maxno'); ?>">Max no of deals to display: <input class="widefat" id="<?php echo $this->get_field_id('maxno'); ?>" name="<?php echo $this->get_field_name('maxno'); ?>" type="text" value="<?php echo attribute_escape($maxno); ?>" /></label></p>
  <p>
    <label for="<?php echo $this->get_field_id('show_own_deals'); ?>">Only show Deals related to this post: 
      <select id="<?php echo $this->get_field_id('show_own_deals'); ?>" name="<?php echo $this->get_field_name('show_own_deals'); ?>">
        <option value="yes" <?php if ( attribute_escape($show_own_deals) == 'yes' ) { print 'selected'; } ?>>yes</option>
        <option value="no" <?php if ( attribute_escape($show_own_deals) == 'no' ) { print 'selected'; } ?>>no</option>
      </select>
    </label>

      <div id="dealsCheckboxes" <?php print $showDeals; ?>>

      <p>or</p>

      
      <p>
         <label for="<?php echo $this->get_field_id('show_own_deals'); ?>">Choose deals to display:<br/> 
      <?php 
      $args = array(
        'posts_per_page'   => -1,
        'offset'           => 0,
        'category'         => '',
        'orderby'          => 'post_date',
        'order'            => 'DESC',
        'include'          => '',
        'exclude'          => '',
        'meta_key'         => '',
        'meta_value'       => '',
        'post_type'        => 'deal',
        'post_mime_type'   => '',
        'post_parent'      => '',
        'post_status'      => 'publish',
        'suppress_filters' => true ); 

      $posts_array = get_posts( $args );
      //debug($posts_array);
      foreach ($posts_array as $deal) {
        $id = $deal->ID;
        $name = $deal->post_title;
        $ticked = array_checked( $id, $selected_deals, false );
        print '<input name="'.$this->get_field_name('selected_deals').'[]" type="checkbox" value="'.$id.'" '. $ticked .'/>';
        print '<label for="'.$this->get_field_id('selected_deals').'[]">'.$name.'</label>';
        print '<br/>';

      
      }

    ?>

      </p>

  </div>
  <script type="text/javascript">
    // Start allowance of jQuery to $ shortcut
    jQuery(document).ready(function($){
      jQuery('#<?php echo $this->get_field_id('show_own_deals'); ?>').change(function() {
        
        if (jQuery(this).val() === 'yes') {
            jQuery('div#dealsCheckboxes').hide();
        } else {
           jQuery('div#dealsCheckboxes').show();
        }
      });
      
    });
  </script>

<?php
  }
 
  function update($new_instance, $old_instance)
  {
    //debug($new_instance);
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['show_own_deals'] = $new_instance['show_own_deals'];
    $instance['selected_deals'] = $new_instance['selected_deals'];
    $instance['maxno'] = $new_instance['maxno'];
    return $instance;
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
    global $post;
    //debug($instance);
 
    echo $before_widget;
    $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . $title . $after_title;;
  
    $deals_to_include = '';
    if ($instance['show_own_deals'] == 'no') {
      if (isset($instance['selected_deals'])) {
        $deals_to_include = implode(",", $instance['selected_deals']);
      }
    } else {
      //get deals for this post
      $deals = get_field('deals', $post->ID);
      $post_deals = array();
      foreach($deals as $deal) {
        array_push($post_deals, $deal->ID);
      }
      //debug($post_deals);
      $deals_to_include = implode(",", $post_deals);
    }

    $maxno = 3;
    if (isset($instance['maxno'])) {
      $maxno = $instance['maxno'];
    }



    $args = array(
      'posts_per_page'   => $maxno,
      'offset'           => 0,
      'category'         => '',
      'orderby'          => 'post_date',
      'order'            => 'DESC',
      'include'          => $deals_to_include,
      'exclude'          => '',
      'meta_key'         => '',
      'meta_value'       => '',
      'post_type'        => 'deal',
      'post_mime_type'   => '',
      'post_parent'      => '',
      'post_status'      => 'publish',
      'suppress_filters' => true ); 

    //Get the deals
    $posts_array = get_posts( $args );

    //loop thorugh deals
    $count = 0;
    foreach ($posts_array as $deal) {
      if ($count < $maxno) {
        $id = $deal->ID;
        $name = $deal->post_title;
        print get_deal_html($deal);
      }
      $count++;

    
    }
 
    echo $after_widget;
  }
}
add_action( 'widgets_init', create_function('', 'return register_widget("Deals_Widget");') );
 
?>
