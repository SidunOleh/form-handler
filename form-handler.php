<?php

class SOVA_WP_Form
{
    public const EMAIL_REGEX = '^\\S+@\\S+\\.\\S+';

    public const PHONE_REGEX = '^\(\d{3}\) \d{3}\-\d{4}$';

    private $action;

    private $rules;

    private $to;

    private $subject;

    private $headers;

    public function __construct( 
        string $action, 
        array $rules, 
        string $to = '', 
        string $subject = '',
        array $headers = []
    ) {
        $this->action = $action;
        $this->rules = $rules;
        $this->to = $to ?: get_option( 'admin_email' );
        $this->subject = $subject ?: get_bloginfo( 'name' );
        $this->headers = $headers;
    }

    public function handle()
    {
        add_action( "wp_ajax_{$this->action}",  [ $this, 'handler' ] );
        add_action( "wp_ajax_nopriv_{$this->action}", [ $this, 'handler' ] );
    }

    public function handler()
    {
        $validated = [];
        $errors = [];
        foreach ( $this->rules as $field => $rule ) {
            $fieldVal = $_POST[ $field ] ?? '';

            if ( ! preg_match( "/{$rule[ 'regex' ]}/u", $fieldVal ) ) {
                $errors[ $field ] = $rule[ 'msg' ] ?? "$field " . __( 'is invalid' );
            } else {
                $validated[ $field ] = $_POST[ $field ];
            }
        }

        if ( isset( $validated[ 'email' ] ) ) {
            $this->headers[] = "Reply-To: {$validated[ 'email' ]} <{$validated[ 'email' ]}>";
        }

        if ( ! empty( $errors ) ) {
            $this->fault( $errors );
        } else {
            $this->send( $validated );
        }
    }

    private function send( array $data )
    {
        $msg = $this->msg( $data );
        wp_mail( $this->to, $this->subject, $msg, $this->headers );

        wp_send_json( [ 'status' => true, ] );
        wp_die();
    }

    private function fault( array $errors )
    {
        wp_send_json( [ 'status' => false, 'errors' => $errors ] );
        wp_die();
    }

    private function msg( array $data )
    {
        $msg = '';
        foreach ( $data as $name => $value ) {
            if ( empty( $name ) or empty( $value ) ) {
                continue;
            }

            $name = explode( '_', $name );
            $name[0] = ucfirst( $name[0] );
            $name = implode( ' ', $name );

            $msg .= "{$name}: {$value}<hr>";
        }

        return $msg;
    }
}
