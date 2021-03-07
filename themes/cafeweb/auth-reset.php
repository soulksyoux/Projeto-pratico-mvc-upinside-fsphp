<?php $v->layout("_theme"); ?>

<article class="auth">
    <div class="auth_content container content">
        <header class="auth_header">
            <h1>Criar uma nova senha</h1>
            <p>Informe uma nova senha e repita para proceder com o processo de recuperação de senha.</p>
        </header>

        <form class="auth_form" action="<?= url("/recuperar/reset") ?>" method="post" enctype="multipart/form-data">
            <div class="ajax_response"><?= flash(); ?></div>
            <input type="text" name="code" value="<?= $code; ?>">
            <?= csrf_input(); ?>

            <label>
                <div class="unlock-alt">
                    <span class="icon-envelope">Nova Senha:</span>
                    <span><a title="Voltar e entrar" href="<?= url("/entrar"); ?>">Voltar e entrar!</a></span>
                </div>
                <input type="password" name="password" placeholder="Nova Senha:" required/>
            </label>

            <label>
                <div class="unlock-alt">
                    <span class="icon-envelope">Repita a Senha:</span>
                    <span><a title="Voltar e entrar" href="<?= url("/entrar"); ?>">Voltar e entrar!</a></span>
                </div>
                <input type="password" name="password_re" placeholder="Repita a Senha:" required/>
            </label>

            <button class="auth_form_btn transition gradient gradient-green gradient-hover">Alterar Senha</button>
        </form>
    </div>
</article>