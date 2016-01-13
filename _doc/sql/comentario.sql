set @row_num = 0;
set @co_post = 0;

select
	c.co_post,
	u.nu_usuario,
	u.fb_id,
	u.no_email,
	u.no_usuario,
	c.nu_comentario,
	c.dt_cadastro,
	c.tx_comentario
from tb_comentario c
join (
	select 
	co_comentario
	from (
		select
		co_post,
		co_comentario,
		if(@co_post <> co_post,@row_num := 0,@row_num := @row_num + 1) row_num,
		(@co_post := co_post) co_post_ctr
		from 
		tb_comentario
		where
		co_post in(10000,9999,10002,10001) and
		st_ativo = 1
	) c
	where c.row_num < 3
) tc on tc.co_comentario = c.co_comentario
join tb_usuario u on u.co_usuario = c.co_usuario
order by c.dt_cadastro desc