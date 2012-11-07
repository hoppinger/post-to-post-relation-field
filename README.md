post-to-post-relation-field
===========================

Post to post relation field for the popular Wordpress plugin Advanced Custom Fields.

## Creating a post to post relation field
This field can be used almost the same as a post object field so it's possible to select the post types from which the user can make a selection.

## Using it
When a post is selected as a relation and then updated the selected post also gets a new meta value of the current post. 

Under the surface the save action gets reversed and also executed. So post A gets a meta value of B and post B gets a meta value of A.

## What happens when a third post comes into the picture?
When a user selects a relation to post A for post C than post post A gets a meta value C. But now post B is stranded because it is still linked to post A. 

When this happens post B also gets a new meta value: `null`
This way post B is no longer stranded and has a no relation. So in short the trail gets cleaned up for you.

## Adding this field
The next step is registering the field with ACF. This code may varry on your project structure.

  <?php
    // in functions.php file
    if( function_exists( 'register_field' ) )
    {
      register_field('Post_to_post', dirname(__File__) . '/fields/post_to_post.php');
    }
  ?>

http://www.advancedcustomfields.com/docs/tutorials/creating-and-registering-your-own-field/