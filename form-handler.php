<?php

/*
Plugin Name: Form handler
Description: Сlass for handling forms
Author: Sidun Oleh 
*/

defined( 'ABSPATH' ) or die;

class SOVA_WP_Form
{
    public const NONCE = 'jfskalfajf4$%$^&kdsj23928ja';

    public const EMAIL_REGEX = '^\\S+@\\S+\\.\\S+';

    public const TEXT_REGEX = '[a-zA-Zа-яёА-ЯЁ \n]{3,}';

    public const PHONE_REGEX = '[0-9\(\)\-\+]{5,}';

    private $action;

    private $fields;

    private $to;

    private $subject;

    public function __construct( string $action, array $fields, string $to = null )
    {
        $this->action = $action;
        $this->fields = $fields;
        $this->to = $to ?: get_option( 'admin_email' );
        $this->subject = get_bloginfo( 'name' ) . ' | ' . __( 'Callback form' );

        $this->hooks_init();
    }

    private function hooks_init()
    {
        add_action( "wp_ajax_{$this->action}",  [ $this, 'handle' ] );
        add_action( "wp_ajax_nopriv_{$this->action}", [ $this, 'handle' ] );

        add_filter( 'wp_mail_content_type', [ $this, 'mail_content_type' ] );
    }

    public function handle()
    {
        $this->verify_nonce();

        $error = false;
        $data = [];
        foreach ( $this->fields as $name => $regex ) {
            if ( ! preg_match( "/$regex/u", ( $_POST[ $name ] ?? '' ) ) ) {
                $error = true;
                break;
            }

            $data[ $name ] = $_POST[ $name ];
        }

        if ( $error ) {
            $this->fault();
        }

        wp_mail( $this->to, $this->subject, $this->message( $data ) );

        $this->success();
    }

    private function verify_nonce()
    {
        wp_verify_nonce( $_POST[ '_wpnonce' ] ?? '', self::NONCE ) ?: wp_die( wp_send_json( [ 'status' => false, ] ) );
    }

    private function success()
    {
        wp_die( wp_send_json( [ 'status' => true, ] ) );
    }

    private function fault()
    {
        wp_die( wp_send_json( [ 'status' => false, ] ) );
    }

    private function message( array $fields )
    {
        $message = '';
        foreach ( $fields as $name => $value ) {
            if ( empty( $value ) ) {
                continue;
            }
            $message .= ucfirst( $name ) . ": {$value}<hr>";
        }

        return $message;
    }

    public function mail_content_type()
    {
        return 'text/html';
    }
}