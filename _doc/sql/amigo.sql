select
	p.co_post,
	p.nu_post,
	u.nu_usuario,
	u.fb_id,
	u.no_email,
	u.no_usuario,
	p.tx_post,
	CONVERT_TZ(p.dt_cadastro,'+00:00','-03:00') dt_cadastro,
	tp.qt_comentario
from
tb_post p
join
(
	select
		p.co_post,
		count(*) qt_comentario
	from
	tb_post p
	join tb_usuario u
		on u.co_usuario = p.co_usuario
	join tb_seguidor s
		on s.co_usuario_seguidor = 1
		and s.co_usuario_seguido = u.co_usuario
	left join tb_comentario c
		on c.co_post = p.co_post
	where 
		c.st_ativo = 1 and
		p.st_ativo = 1 and
		u.st_ativo = 1
	group by p.co_post
	order by p.dt_cadastro desc
	limit 10
) tp on tp.co_post = p.co_post
join tb_usuario u on u.co_usuario = p.co_usuario