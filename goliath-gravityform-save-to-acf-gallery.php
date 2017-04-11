<?php
/*
Plugin Name: Goliath Gravity Forms Save media to post
Description: Permet de sauvegarder un media dans une meta via Gravity Forms
Version: 1.0
Author: Studio Goliath
Author URI: https://www.studio-goliath.com/
*/

class GGravityformSaveToACFGallery
{

    function __construct()
    {

        add_action( 'gform_after_create_post', array( $this, 'save_form_meta' ), 10, 3 );
    }


    public function save_form_meta( $post_id, $entry, $form )
    {

        // Check if $entry has upload
        $upload_fields = $this->get_upload_fields( $form );

        foreach ( $upload_fields as $field_id => $post_meta){

            $images_urls = json_decode( $entry[ $field_id ] );

            $images_id = array();
            foreach ( $images_urls as $image_url ){

                $images_id[] = $this->create_image_id ($post_id, $image_url );

            }

            if( $images_id ){

                update_post_meta( $post_id, $post_meta, $images_id );
            }
        }

    }

    private function get_upload_fields( $form )
    {
        $upload_file = array();

        foreach ( $form['fields'] as $fields ){

            if( $fields instanceof GF_Field_FileUpload && $fields->type == 'post_custom_field' ){

                $upload_file[ $fields['id'] ] = $fields['postCustomFieldName'];
            }


        }

        return $upload_file;
    }

    /**
     * Create the image and return the new media upload id.
     *
     * @see https://gist.github.com/hissy/7352933
     *
     * @see http://codex.wordpress.org/Function_Reference/wp_insert_attachment#Example
     */
    private function create_image_id( $post_id, $url ) {


        $filename = basename( $url );

        $upload_file = wp_upload_bits( $filename, null, file_get_contents( $url ) );

        if ( ! $upload_file['error'] ) {
            $wp_filetype = wp_check_filetype( $filename, null );
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_parent' => $post_id,
                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $post_id );

            if( ! is_wp_error( $attachment_id ) ) {

                require_once( ABSPATH . 'wp-admin/includes/image.php' );

                $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
                wp_update_attachment_metadata( $attachment_id, $attachment_data );

                return $attachment_id;
            }
        }

        return false;

    } // end function get_image_id
}


add_action( 'init', function(){ new GGravityformSaveToACFGallery(); } );
