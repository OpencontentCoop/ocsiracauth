<div class="warning alert alert-warning">
    <h2>{"Something went wrong..."|i18n("sirac")}</h2>
    <p>{"Contact support and report that you have received an error with code %code"|i18n("sirac",,hash('%code', '<code>30</code>'))}</p>
</div>

{if $embed_content}
    {$embed_content}
{/if}
