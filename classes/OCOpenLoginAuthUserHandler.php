<?php


class OCOpenLoginAuthUserHandler extends OCSiracAuthUserHandler
{
    public function __construct()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_USER'])
            && base64_encode(base64_decode($_SERVER['HTTP_X_FORWARDED_USER'])) == $_SERVER['HTTP_X_FORWARDED_USER']){
            $userData = (array)json_decode(base64_decode($_SERVER['HTTP_X_FORWARDED_USER']), true);
            foreach ($userData as $key => $value){
                $_SERVER['HTTP_X_FORWARDED_USER_' . strtoupper($key)] = $value;
            }
        }
        parent::__construct();

        $this->mappedVars['UserLogin'] = str_replace('TINIT-', '', $this->mappedVars['UserLogin']);
        $this->mappedVars['FiscalCode'] = str_replace('TINIT-', '', $this->mappedVars['FiscalCode']);
        $this->mappedVars['Attributes']['fiscal_code'] = str_replace('TINIT-', '', $this->mappedVars['Attributes']['fiscal_code']);
    }

}