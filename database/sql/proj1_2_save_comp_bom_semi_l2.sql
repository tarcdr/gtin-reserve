create or replace NONEDITIONABLE procedure proj1_2_save_comp_bom_semi_l2(
  P_BOM_SEMI_LV2_ID varchar2,
  P_MATTYPE         varchar2,
  P_SUBMATTYPE      varchar2,
  P_PRODUCT_CAT     varchar2,
  P_PROD_SUB_CAT    varchar2,
  P_SITE            varchar2,
  P_COMPONENT_ID    varchar2,
  P_SEARCH_DESC     varchar2,
  P_COMP_DESC_EN    varchar2,
  P_COMP_DESC_TH    varchar2,
  P_UOM             varchar2,
  P_USER_ROLE       varchar2,
  P_USER            varchar2,
  P_ERROR           out varchar2
) is
  v_no number := 0;
begin
  begin
    select nvl(max(to_number(no)), 0)
      into v_no
      from PROJ1_2_DML_SEMI_L2_COMP_M5;
  exception
    when others then
      v_no := 0;
  end;

  update PROJ1_2_DML_SEMI_L2_COMP_M5
     set SEARCH_DESCRIPTION  = P_SEARCH_DESC,
         FULL_DESCRIPTION_EN = P_COMP_DESC_EN,
         FULL_DESCRIPTION_TH = P_COMP_DESC_TH,
         UOM                 = P_UOM,
         USER_UPDATE         = P_USER,
         UPDATE_DATE         = sysdate
   where trim(MATERIAL_ID_M5) = trim(P_COMPONENT_ID)
     and trim(SEMI_FG_LV2_BOM_NO) = trim(P_BOM_SEMI_LV2_ID);

  if SQL%ROWCOUNT = 0 then
    insert into PROJ1_2_DML_SEMI_L2_COMP_M5(
      NO,
      MATERIAL_ID_M5,
      SEARCH_DESCRIPTION,
      FULL_DESCRIPTION_EN,
      FULL_DESCRIPTION_TH,
      SEMI_FG_LV2_BOM_NO,
      SITE,
      UOM,
      STATUS_ROW,
      USER_ROLE,
      USER_CREATE,
      CREATE_DATE,
      USER_UPDATE,
      UPDATE_DATE
    ) values (
      nvl(v_no, 0) + 1,
      P_COMPONENT_ID,
      P_SEARCH_DESC,
      P_COMP_DESC_EN,
      P_COMP_DESC_TH,
      P_BOM_SEMI_LV2_ID,
      P_SITE,
      P_UOM,
      'INS',
      P_USER_ROLE,
      P_USER,
      sysdate,
      null,
      null
    );

    update PROJ1_2_DML_COMP_M5_TP
       set STATUS_USED = 'SAVED',
           CREATE_DATE_TMP = sysdate
     where MATERIAL_ID_M5_TMP = P_COMPONENT_ID;
  end if;

  P_ERROR := '';
exception
  when others then
    P_ERROR := 'ERR-001';
end;
