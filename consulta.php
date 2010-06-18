<?php /* Template Name: Consulta */ ?>

<?php get_header(); ?>

<script type="text/javascript">
/* I hate to type jQuery all the time */
$ = jQuery;

/* Useful values when building urls, using PHP to generate them. */
blogUrl = "<?php bloginfo('url'); ?>";
templateUrl = "<?php bloginfo('template_url'); ?>";

/* Paragraph that is currently loaded in the comments list. It starts
 * empty and is filled by the `loadComments()' function. */
loadedParagraph = null;


function formatDate(date) {
    return date;
}

function formatOpinion(opinion) {
    switch (opinion) {
    case 'concordo':
        return 'Concordo com o Dispositivo';
    case 'concordo-com-ressalvas':
        return 'Concordo com o Dispositivo com ressalvas';
    case 'discordo':
        return 'Discordo do Dispositivo';
    default:
        return opinion;
    }
}

function formatProposal(proposal) {
    switch (proposal) {
    case 'alteracao':
        return 'Alteração no texto';

    case 'exclusao':
        return 'Exclusão do dispositivo';

    case 'retorno':
        return 'Retorno à redação original';

    case 'acrescimo':
        return 'Acréscimo de um novo dispositivo';

    default:
        return proposal;
    }
}

function filterContent(content) {
    while (content.indexOf('\n') != -1)
        content = content.replace('\n', '<br />');
    return content;
}

function populateComments(container, comments) {
  container.html('');
  /* No comments yet */
  if (comments.length == 0) {
    container.html(
                   '<li class="comment">' +
                   'Não há propostas para o dispositivo selecionado.' +
                   '</li>');
    return;
  }

    for (var i = 0; i < comments.length; i++) {
      var obj = comments[i];
      var infoUser = $('<div>')
        .addClass('infoUser')
        .append($('<span>')
                .addClass('date')
                .html(formatDate(obj.comment_date)))
        .append($('<span>')
                .addClass('user')
                .html(obj.user_name));
      var li = $('<li>')
        .addClass('comment')
        .append(infoUser)
        .append('<strong>Opinião</strong>')
        .append($('<p>').html(formatOpinion(obj.meta.opiniao)));

      if (obj.meta.proposta) {
        li.append('<strong>Proposta</strong>');
        li.append($('<p>').html(formatProposal(obj.meta.proposta)));
      }

      if (obj.meta.contribuicao) {
        li.append('<strong>Contribuição</strong>');
        li.append($('<p>').html(filterContent(obj.meta.contribuicao)));
      }

      if (obj.meta.justificativa) {
        li.append('<strong>Justificativa</strong>');
        li.append($('<p>').html(filterContent(obj.meta.justificativa)));
      }

      if (obj.tags) {
        var ul = $('<ul>');
        li.append('<strong>Tags</strong>');
        for (var t = 0; t < obj.tags.length; t++)
          ul.append($('<li>').append(obj.tags[t].name));
        li.append(ul);
      }

      li.appendTo(container);
    }
}

function setup_cpages(ct, total, current, fun) {
  ct.html("");
  for (var i = 0; i < total+1; i++) {
    if (i == current) {
      ct.append($("<li>").append(i+1))
        } else {
      ct.append($("<li>")
                .append(
                        $("<a>")
                        .html(i+1)
                        .attr("title","Página "+(i+1))
                        .attr("href", "#")
                        .attr("name", i)
                        .click(function() { fun($(this).attr("name")) })))
    }
  }
}

function paginateComments(container, comments) {
  var ct = $("#comments_pagination");
  var total_pages =  Math.floor((comments.length+1) / 5);
  
  function setCommentsPage(page) {
    setup_cpages(ct, total_pages, page, setCommentsPage);
    populateComments(container, comments.slice(page*5, 5 + (page*5)));
  }

  setup_cpages(ct, total_pages, 0, setCommentsPage);
}

function loadComments(paragraphId, postId) {
    var query = '{"method":"get_paragraph_comments","params":["' +
        paragraphId + '",' + postId + ']}'
    var container = $('#commentContainer');

    /* Testing if the paragraph asked to be loaded is already the
     * current one, if it's not we set the loaded paragraph. */
    if (loadedParagraph == paragraphId)
        return;
    else
        loadedParagraph = paragraphId;

    
    /* Loading gif */
    container.html(
        '<li class="comment">' +
        '<img src="' + templateUrl + '/images/loading.gif" />' +
        '</li>');

    /* Getting comment list */

    $.getJSON(blogUrl, {dialogue_query:query}, function (comments) {
        var i;        

        /* Building the html of the comment list */

        //paginate here using $(#comments_pagination)
        // create a function to populate the comments (the code for this is below)
        // when page changes, call that func with a subset of the comments.
        if (comments.length > 5) {
          populateComments(container, comments.slice(0,5));
          paginateComments(container, comments);
        } else {
          populateComments(container, comments);
        }
    });
}

$(document).ready(function () {
    $('.comment-pp').click(function () {
        var postId = $('input[name=comment_post_ID]', $(this)).val();
        var paragraphId =
            $('input[name=dialogue_comment_paragraph]', $(this)).val();
        loadComments(paragraphId, postId);

        /* marking selected paragraph as the active, comments of this
         * `selected' paragraph are going to be shown in the right
         * column. */
        $('.comment-pp').removeClass('active');
        $(this).addClass('active');

        /* Moving the comments column to near the clicked post */
        var margin = $(this).offset().top - 220;
        $('#comments').css('margin-top', margin);
    });

    /* Loading comments from the first paragraph */
    var postIdExpr = $('input[name=comment_post_ID]');
    var paragraphIdExpr = $('input[name=dialogue_comment_paragraph]');
    if (postIdExpr.length > 0 && paragraphIdExpr.length > 0) {
        var paragraphId = $(paragraphIdExpr[0]);

        /* Marking comment paragraph as the active one */
        paragraphId.parents('div.comment-pp').addClass('active');

        /* Actually loading comments from the found paragraph */
        loadComments(paragraphId.val(), $(postIdExpr[0]).val());
    }
});
</script>

<div id="content">
  <?php
    query_posts('category_name=Consulta');
    while (have_posts()) : the_post();
  ?>
  <div class="post">
    <span class="title"><?php the_title(); ?></span>
    <span class="counter">
      <?php
        $comments = get_comment_count($post->ID);
        echo "(" . $comments['approved'] . ")";
      ?>
    </span>
    <?php the_content(); ?>
  </div>
  <?php endwhile; ?>

  <div id="comments">
    <div id="navegaComments">
      <h2 class="widgettitle">Propostas</h2>
      <ol id="comments_pagination">
<!--
        <li><a href="#" title="Página 1">1</a></li>
        <li><a href="#" title="Página 2">2</a></li>
        <li><a href="#" title="Página 3">3</a></li>
        <li><a href="#" title="Página 4">4</a></li>	
        <li><a href="#" title="Página 5">5</a></li>
-->
      </ol>
<!--
      <form action="get">
        <label for="">Campo busca de comentários</label>
        <input type="text" name="busca nos comentários" />
      </form>
-->
    </div><!--fim #navegaComments-->
    <ul id="commentContainer"></ul>
  </div><!--fim #comments-->

</div>

<?php get_footer(); ?>
