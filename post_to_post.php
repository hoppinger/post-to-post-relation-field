<?php

/*
 *  Advanced Custom Fields - Post to post relation
 *  
 *  Documentation: 
 *  - This field makes it possible to select a relation between two posts. 
 *  - The relation is a 1 to 1 relation. 
 *  - When a post is saved the related post get the current post as it's relation.
 *  - If a post changes it relation to a new post then the old relation will be set to null.
 */
 
 
class Post_to_post extends acf_Field
{

  /*--------------------------------------------------------------------------------------
  *
  * Constructor
  *
  * @author Hoppinger
  * @since 1.0.0
  * 
  *-------------------------------------------------------------------------------------*/
  
  function __construct($parent) {
    
    parent::__construct($parent);
      
    $this->name = 'post_to_post';
    $this->title = __("Post to post relation",'acf');
    
  }

  
  /*--------------------------------------------------------------------------------------
  *
  * create_options
  * - this function is called from core/field_meta_box.php to create extra options
  * for your field
  *
  * @params
  * - $key (int) - the $_POST obejct key required to save the options to the field
  * - $field (array) - the field object
  *
  * @author Hoppinger
  * @since 1.0.0
  * 
  *-------------------------------------------------------------------------------------*/
  
  function create_options($key, $field)
  { 
    // defaults
    $defaults = array(
      'post_type'   =>  '',
      'multiple'    =>  '0',
      'allow_null'  =>  '0',
      'taxonomy'    =>  array('all'),
    );
    
    $field = array_merge($defaults, $field);

    ?>
    <tr class="field_option field_option_<?php echo $this->name; ?>">
      <td class="label">
        <label for=""><?php _e("Post Type",'acf'); ?></label>
      </td>
      <td>
        <?php 
        
        $choices = array(
          ''  =>  __("All",'acf')
        );
        
        $post_types = get_post_types( array('public' => true) );
        
        foreach( $post_types as $post_type )
        {
          $choices[$post_type] = $post_type;
        }
        
        $this->parent->create_field(array(
          'type'  =>  'select',
          'name'  =>  'fields['.$key.'][post_type]',
          'value' =>  $field['post_type'],
          'choices' =>  $choices,
          'multiple'  =>  '1',
        ));
        ?>
      </td>
    </tr>
    <?php
  }
  
  
  /*--------------------------------------------------------------------------------------
  *
  * pre_save_field
  * - this function is called when saving your acf object. Here you can manipulate the
  * field object and it's options before it gets saved to the database.
  *
  * @author Hoppinger
  * @since 1.0.0
  * 
  *-------------------------------------------------------------------------------------*/
  
  function pre_save_field($field)
  {
    // do stuff with field (mostly format options data)
    
    return parent::pre_save_field($field);
  }
  
  
  /*--------------------------------------------------------------------------------------
  *
  * create_field
  *
  * @author Hoppinger
  * @since 1.0.0
  * 
  *-------------------------------------------------------------------------------------*/
  
  function create_field($field)
  {
    // vars
    $args = array(
      'numberposts' => -1,
      'post_type' => null,
      'orderby' => 'title',
      'order' => 'ASC',
      'post_status' => array('publish', 'private', 'draft', 'inherit', 'future'),
      'suppress_filters' => false,
    );
    
    $defaults = array(
      'multiple'    =>  '0',
      'post_type'   =>  false,
      'allow_null'  =>  '1',
    );
    

    $field = array_merge($defaults, $field);
    
    
    // load all post types by default
    if( !$field['post_type'] || !is_array($field['post_type']) || $field['post_type'][0] == "" )
    {
      $field['post_type'] = get_post_types( array('public' => true) );
    } 
    
    // Change Field into a select
    $field['type'] = 'select';
    $field['choices'] = array();
    $field['optgroup'] = false;
    
    
    foreach( $field['post_type'] as $post_type )
    {
      // set post_type
      $args['post_type'] = $post_type;
      
      
      // set order
      if( is_post_type_hierarchical($post_type) )
      {
        $args['sort_column'] = 'menu_order, post_title';
        $args['sort_order'] = 'ASC';

        $posts = get_pages( $args );
      }
      else
      {
        $posts = get_posts( $args );
      }
      
      
      if($posts)
      {
        foreach( $posts as $post )
        {
          // find title. Could use get_the_title, but that uses get_post(), so I think this uses less Memory
          $title = '';
          $ancestors = get_ancestors( $post->ID, $post->post_type );
          if($ancestors)
          {
            foreach($ancestors as $a)
            {
              $title .= '–';
            }
          }
          $title .= ' ' . apply_filters( 'the_title', $post->post_title, $post->ID );
          
          
          // status
          if($post->post_status != "publish")
          {
            $title .= " ($post->post_status)";
          }
          
          // WPML
          if( defined('ICL_LANGUAGE_CODE') )
          {
            $title .= ' (' . ICL_LANGUAGE_CODE . ')';
          }
          
          // add to choices
          if( count($field['post_type']) == 1 )
          {
            $field['choices'][ $post->ID ] = $title;
          }
          else
          {
            // group by post type
            $post_type_object = get_post_type_object( $post->post_type );
            $post_type_name = $post_type_object->labels->name;
          
            $field['choices'][ $post_type_name ][ $post->ID ] = $title;
            $field['optgroup'] = true;
          }
          
          
        }
        // foreach( $posts as $post )
      }
      // if($posts)
    }
    // foreach( $field['post_type'] as $post_type )
    
    
    // create field
    $this->parent->create_field( $field );
    
    
  }

  
  /*--------------------------------------------------------------------------------------
  *
  * update_value
  * - this function is called when saving a post object that your field is assigned to.
  * the function will pass through the 3 parameters for you to use.
  *
  * @params
  * - $post_id (int) - usefull if you need to save extra data or manipulate the current
  * post object
  * - $field (array) - usefull if you need to manipulate the $value based on a field option
  * - $value (mixed) - the new value of your field.
  *
  * @author Hoppinger
  * @since 1.0.0
  * 
  *-------------------------------------------------------------------------------------*/
  
  function update_value($post_id, $field, $value)
  {
    $previous_relation_id = get_post_meta( $post_id, $field['name'], true ); 

    // change the old relation post to null if this post relation has changed
    // this is a 1 to 1 relation so we want to clean up the history
    if( $previous_relation_id != $value ){
      $previous_relation_post = get_post( $previous_relation_id );
      if( $previous_relation_post ){
        parent::update_value( $previous_relation_post->ID, $field, null );    
      }
    }

    // save value on the relation post
    parent::update_value($value, $field, $post_id);
    // save own value
    parent::update_value($post_id, $field, $value);
  }
  
  
  /*--------------------------------------------------------------------------------------
  *
  * get_value
  * - called from the edit page to get the value of your field. This function is useful
  * if your field needs to collect extra data for your create_field() function.
  *
  * @params
  * - $post_id (int) - the post ID which your value is attached to
  * - $field (array) - the field object.
  *
  * @author Hoppinger
  * @since 1.0.0
  * 
  *-------------------------------------------------------------------------------------*/
  
  function get_value($post_id, $field)
  {
    // get value
    $value = parent::get_value($post_id, $field);
    
    // format value
    
    // return value
    return $value;    
  }
  
  
  /*--------------------------------------------------------------------------------------
  *
  * get_value_for_api
  * - called from your template file when using the API functions (get_field, etc). 
  * This function is useful if your field needs to format the returned value
  *
  * @params
  * - $post_id (int) - the post ID which your value is attached to
  * - $field (array) - the field object.
  *
  * @author Hoppinger
  * @since 1.0.0
  * 
  *-------------------------------------------------------------------------------------*/
  
  function get_value_for_api($post_id, $field)
  {
    // get value
    $value = parent::get_value($post_id, $field);
    
    
    // no value?
    if( !$value )
    {
      return false;
    }
    
    
    // null?
    if( $value == 'null' )
    {
      return false;
    }
    
    
    // multiple / single
    if( is_array($value) )
    {
      // find posts (DISTINCT POSTS)
      $posts = get_posts(array(
        'numberposts' => -1,
        'post__in' => $value,
        'post_type' =>  get_post_types( array('public' => true) ),
        'post_status' => array('publish', 'private', 'draft', 'inherit', 'future'),
      ));
  
      
      $ordered_posts = array();
      foreach( $posts as $post )
      {
        // create array to hold value data
        $ordered_posts[ $post->ID ] = $post;
      }
      
      
      // override value array with attachments
      foreach( $value as $k => $v)
      {
        // check that post exists (my have been trashed)
        if( isset($ordered_posts[ $v ]) )
        {
          $value[ $k ] = $ordered_posts[ $v ];
        }
      }
      
    }
    else
    {
      $value = get_post($value);
    }
    
    
    // return the value
    return $value;
  }
    
}