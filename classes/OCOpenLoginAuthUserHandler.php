<?php


class OCOpenLoginAuthUserHandler extends OCSiracAuthUserHandler
{
    public function __construct()
    {
        parent::__construct();

        $this->mappedVars['UserLogin'] = str_replace('TINIT-', '', $this->mappedVars['UserLogin']);
        $this->mappedVars['FiscalCode'] = str_replace('TINIT-', '', $this->mappedVars['FiscalCode']);
        $this->mappedVars['Attributes']['fiscal_code'] = str_replace('TINIT-', '', $this->mappedVars['Attributes']['fiscal_code']);
    }

}