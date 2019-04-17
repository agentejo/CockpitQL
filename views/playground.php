<div class="uk-container-breakout">
    <iframe id="graphiQL" src="@route('/cockpitql/graphiql')" width="100%" frameborder="0"></iframe>
</div>

<script>

    document.addEventListener("DOMContentLoaded", function(event) {

        let headerHeight = App.$('.app-header').outerHeight(),
            marginTop    = App.$(graphiQL).offset().top - headerHeight;

        graphiQL.style.height = `calc(100vh - ${headerHeight}px`;
        graphiQL.style.marginTop = `-${marginTop}px`;
    });

</script>