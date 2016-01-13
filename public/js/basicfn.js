function formSend(arg) {
    var bt_enviar = (arg.bt_enviar ? arg.bt_enviar : '#bt-enviar');
    var ld_enviar = (arg.ld_enviar ? arg.ld_enviar : '#ls-enviar');

    $(arg.no_pai + ' ' + bt_enviar).attr('disabled', 'disabled');
    $(arg.no_pai + ' ' + ld_enviar).show();

    var data = '';
    if (arg.data) {
        data += arg.data;
    }

    $(
        arg.no_pai + ' input, ' +
        arg.no_pai + ' textarea, ' +
        arg.no_pai + ' select'
    ).each(function () {
        if (!$(this).attr('disabled') && $(this).attr('name')) {
            if (
                (
                    (
                        $(this).attr('type') == 'checkbox' ||
                        $(this).attr('type') == 'radio'
                    ) &&
                    $(this).is(':checked')
                ) ||
                (
                    $(this).attr('type') != 'checkbox' &&
                    $(this).attr('type') != 'radio'
                )
            ) {
                data += '&' + $(this).attr('name') + '=' + encodeURIComponent($(this).val());
            }
        }
    });

    $.ajax({
        url: arg.url,
        type: 'POST',
        data: data,
        dataType: (arg.dataType ? arg.dataType : 'JSON'),
        beforeSend: function(){
            if (arg.loading) {
                arg.loading();
            }
        },
        success: function (j) {
            if (arg.callback) {
                arg.callback(j);
            } else {
                alert(j.msg);

                $(arg.no_pai + ' ' + bt_enviar).hide();
                $(arg.no_pai + ' ' + ld_enviar).off('click');
            }
        }
    });
}

function boxPublicar(j) {
    var textarea = $(j.pai + ' [name="tx-publicar"]');
    var fntextarea = function () {
        $(this).val($(this).val().substr(0, 200));
        $(j.pai + ' .contador').text($(this).val().length + '/200');
    }

    $(textarea).keyup(fntextarea).keypress(fntextarea);

    $(j.pai + ' .b-bt-enviar').click(function () {
        $(textarea).val($(textarea).val().trim());

        if ($(textarea).val().length > 0) {
            $(this).attr('disabled', '1');
            $(textarea).attr('disabled', '1');
            $(j.pai).fadeTo("fast", 0.5);
            $(j.pai + ' .load').show();

            $.ajax({
                url: j.url,
                type: 'POST',
                dataType: 'JSON',
                data: {
                    tx_publicar: encodeURIComponent($(textarea).val())
                },
                success: function (jx) {
                    if (!jx.status) {
                        alert((jx.msg ? jx.msg : "Error!"));
                    }

                    $(j.pai + ' .b-bt-enviar').removeAttr('disabled');
                    $(textarea).removeAttr('disabled');
                    $(j.pai).fadeTo("fast", 1);
                    $(textarea).val('');
                    $(j.pai + ' .contador').text('0/200');
                    $(j.pai + ' .load').hide();

                    if (j.callback) {
                        j.callback(jx);
                    }
                },
                error: function () {
                    alert("Error!");
                }
            });
        }
    });
}

function popup(j) {
    var hmtl = '';
    html = '<div class="popup">';
    html += '<div class="close"></div>';
    html += '<div class="popcontent"></div>';
    html += '</div>';

    $('.popup').remove();
    $(j.pai).append(html);
    $('.popup .close').click(function () {
        $('.popup').remove();
    });
}

function comentar(postid, postli) {
    popup({
        pai: $('[postli="' + postli + '"] > .pcontent .action')
    });
    $.ajax({
        url: $('#baseurl').val() + 'action/comentariofield',
        data: {
            nu_post: postid
        },
        success: function (html) {
            $('.popup .popcontent').html(html);
        }
    });
}

function getPost(j) {
    var pai = "#post-" + j.tipo

    $.ajax({
        url: $('#baseurl').val() + 'action/getpost',
        type:"POST",
        data: {
            tipo: j.tipo,
            uptime: (j.uptime ? $(pai + ' > li:last-child').attr('utc') : null),
            nu_usuario: (j.uptime ? $('#nu_usuario').val() : null)
        },
        success: function (html) {
            if (j.uptime) {
                $(pai).append(html);
            } else {
                $(pai).html(html);
            }
        }
    });
}

function getComentario(j) {
    var ulpost = '[postid="' + j.nu_post + '"]';
    var ulid = ulpost + ' .pcomment';

    if (!j.start) {
        $(ulid).fadeTo("fast", 0.5);
    }

    if (!$(ulid).is('*')) {
        $(ulpost).append('<ul class="pcomment"></ul>')
    }

    $.ajax({
        url: $('#baseurl').val() + 'action/getcomentario',
        type: "POST",
        data: {
            start: j.start,
            nu_post: j.nu_post,
            limit: j.limit
        },
        success: function (html) {
            if (j.start) {
                $(ulid).append(html);
            } else {
                $(ulid).html(html);
                $(ulid).fadeTo("fast", 1);
            }
        }
    });
}

function setSeguidor(modo, nu_usuario) {
    var seguidorButton = $('#bt-seguidor a');

    $(seguidorButton).animate({
        opacity: 0,
    }, 'fast', function () {
        $(this).addClass('bta-hidden');
        $(this).parent().addClass('ld-onblack');
    });

    $.ajax({
        url: $('#baseurl').val() + 'action/setseguidor',
        type: "POST",
        dataType: 'JSON',
        data: {
            nu_usuario: nu_usuario,
            modo: modo
        },
        success: function (jx) {
            if (!jx.status) {
                alert(jx.msg);
            }

            $(seguidorButton).animate({
                opacity: 1,
            }, 'fast', function () {
                $(this).addClass('bta-hidden');
                $(this).parent().html(jx.html);
            });
        }
    });
}

$(document).on('scroll', function () {
    if ($(window).scrollTop() + $(window).height() >= $(this).height()) {
        if ($('#post-usuario').is('*')) {
            getPost({
                tipo: 'usuario',
                uptime: true
            });
        }
        if ($('#post-rede').is('*')) {
            getPost({
                tipo: 'rede',
                uptime: true
            });
        }
        if ($('#post-amigo').is('*')) {
            getPost({
                tipo: 'amigo',
                uptime: true
            });
        }
    }
});