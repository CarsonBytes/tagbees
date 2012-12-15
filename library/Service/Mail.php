<?php
class Service_Mail{
    protected $zendMail;
    
    protected $recipient_email;
    protected $recipient_name;
    
    protected $sender_email;
    protected $sender_name;
    
    protected $template_name; 
      
    protected $template_vars;
    
    public function __construct ()
    {
    }

    public function setTemplate ($filename)
    {
        $this->template_name = $filename;
    }

    public function setTemplateVars ($vars)
    {
        $this->template_vars = $vars;
    }
    
    public function setRecipientEmail ($email)
    {
        $this->recipient_email = $email;
    }
    
    public function setRecipientName ($name)
    {
        $this->recipient_name = $name;
    }
    
    public function setSenderEmail ($email)
    {
        $this->sender_email = $email;
    }
    
    public function setSenderName ($name)
    {
        $this->sender_name = $name;
    }
    
    public function send () {
        /*
        $settings = array(
                    'ssl'=>'ssl',
                    'port'=>465,
                    'auth' => 'login',
                    'username' => 'youremail@gmail.com',
                    'password' => 'YOUR_PASSWORD'
                );
        */
        $settings = Zend_Registry::get('config_ini')->email->transport->toArray();
        $transport = new Zend_Mail_Transport_Smtp(Zend_Registry::get('config_ini')->email->host, $settings);
        
        $email_from = ($this->sender_email != NULL) ? $this->sender_email : Zend_Registry::get('config_ini')->email->defaultFrom->email;
        $name_from = ($this->sender_name != NULL) ? $this->sender_name : Zend_Registry::get('config_ini')->email->defaultFrom->name;
        $email_to = ($this->recipient_email != NULL) ? $this->recipient_email : Zend_Registry::get('config_ini')->email->defaultReplyTo->email;
        $name_to = ($this->recipient_name != NULL) ? $this->recipient_name : Zend_Registry::get('config_ini')->email->defaultReplyTo->name;
        
        $view = new Zend_View();
        $view->addScriptPath(Zend_Registry::get('config_ini')->email->templatePath);
        $view->template_vars = $this->template_vars;
        $html = $view->render($this->template_name . '.phtml');
        
        $subject = Common::config()->email_templates->{$this->template_name}->subject;
        
        $mail = new Zend_Mail('utf-8');
        //$mail->setBodyText('My Nice Test Text');
        $mail->setBodyHtml($html);
        //$mail->setReplyTo($email_from, $name_from);
        $mail->setFrom($email_from, $name_from);
        $mail->addTo($email_to, $name_to);
        $mail->setSubject($subject);
        $mail->send($transport);
    }
}