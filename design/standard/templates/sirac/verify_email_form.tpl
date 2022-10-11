<section class="hgroup">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">

            <div class="alert alert-info text-center">
                <i class="fa fa-envelope-o fa-5x"></i>
                <h3>{'Verifica indirizzo email'|i18n('ocsirac/verify_email')}</h3>
                <p class="lead">{'Enter the code that was sent to your inbox'|i18n('ocsirac/verify_email')}</p>
                <form action="{'/verify_sirac_user/verify_email'|ezurl(no)}" method="post">
                    <div class="form-group">
                        <label for="VerifyCode">{'Verification code'|i18n('ocsirac/verify_email')}</label>
                        <input id="VerifyCode" name="VerifyCode" type="text" class="form-control input-lg" />
                    </div>
                    <div class="form-group clearfix">
                        <input type="submit" name="VerifyCodeAction" class="btn btn-lg btn-info pull-right" value="{'Invia'|i18n('ocsirac/verify_email')}" />
                    </div>
                </form>
            </div>


        </div>
    </div>
</section>