<?php $v->layout("_theme"); ?>

<section class="blog_page">
    <header class="blog_page_header">
        <h1><?= ($title ?? "BLOG"); ?></h1>
        <p><?= ($search ?? "Confira nossas dicas para controlar melhor suas contas"); ?></p>
        <form name="search" action="<?= url("/blog/buscar"); ?>" method="post" enctype="multipart/form-data">
            <label>
                <input type="text" name="s" placeholder="Encontre um artigo:"/>
                <button class="icon-search icon-notext"></button>
            </label>
        </form>
    </header>


    <?php if(empty($blog) && !empty($search)): ?>
        <!--EMPTY CONTENT-->
        <div class="content content">
            <div class="empty_content">
                <h3 class="empty_content_title">Sua pesquisa não retornou resultados...</h3>
                <p class="empty_content_desc">Voce pesquisou por <b><?= $search ?></b>, tente fazer uma nova pesquisa com termos diferentes.</p>
                <a class="empty_content_btn gradient gradient-green gradient-hover radius"
                   href="<?= url("/blog"); ?>" title="Blog">Voltar ao blog</a>
            </div>
        </div>

    <?php elseif (empty($blog)): ?>
        <div class="content content">
            <div class="empty_content">
                <h3 class="empty_content_title">Conteudo ainda não disponivel</h3>
                <p class="empty_content_desc">Estamos a preparar o conteudo de qualidade para ti!</p>
                <a href="<?= url("/blog"); ?>" title="Blog"
                   class="empty_content_btn gradient gradient-green gradient-hover radius">Voltar ao blog</a>
            </div>
        </div>

    <?php else: ?>
        <!--BLOG-->
        <div class="blog_content container content">
            <div class="blog_articles">
                <?php foreach($blog as $post): ?>
                    <?php $v->insert("blog-list", ["post" => $post]); ?>
                <?php endforeach; ?>
            </div>

            <?= $paginator; ?>
        </div>
    <?php endif; ?>

</section>