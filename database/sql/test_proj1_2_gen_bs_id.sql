set serveroutput on size unlimited

declare
  -- Edit these values before running.
  -- P_TYPE_BS: 1 = FG, 2 = BRAND, 3 = NOT ALL
  p_type_bs        varchar2(50)   := '1';
  p_mattype        varchar2(50)   := '6';
  p_sub_mattype    varchar2(50)   := '0';
  p_material_id_fg varchar2(100)  := '10EL00005';
  p_brand          varchar2(100)  := null;
  p_site           varchar2(50)   := '01';
  p_user_login     varchar2(100)  := 'ADMIN';

  p_bs_id          varchar2(4000);
  p_bom_bs_id      varchar2(4000);
  p_error          varchar2(4000);
begin
  PROJ1_2_GEN_BS_ID(
    p_type_bs,
    p_mattype,
    p_sub_mattype,
    p_material_id_fg,
    p_brand,
    p_site,
    p_user_login,
    p_bs_id,
    p_bom_bs_id,
    p_error
  );

  dbms_output.put_line('P_TYPE_BS        = ' || nvl(p_type_bs, '<null>'));
  dbms_output.put_line('P_MATTYPE        = ' || nvl(p_mattype, '<null>'));
  dbms_output.put_line('P_SUB_MATTYPE    = ' || nvl(p_sub_mattype, '<null>'));
  dbms_output.put_line('P_Material_ID_FG = ' || nvl(p_material_id_fg, '<null>'));
  dbms_output.put_line('P_BRAND          = ' || nvl(p_brand, '<null>'));
  dbms_output.put_line('P_SITE           = ' || nvl(p_site, '<null>'));
  dbms_output.put_line('P_USER_LOGIN     = ' || nvl(p_user_login, '<null>'));
  dbms_output.put_line('P_BS_ID          = ' || nvl(p_bs_id, '<null>'));
  dbms_output.put_line('P_BOM_BS_ID      = ' || nvl(p_bom_bs_id, '<null>'));
  dbms_output.put_line('P_ERROR          = ' || nvl(p_error, '<null>'));
end;
/
