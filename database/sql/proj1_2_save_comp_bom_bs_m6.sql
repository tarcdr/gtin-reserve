create or replace NONEDITIONABLE procedure PROJ1_2_SAVE_COMP_BOM_BS_M6(
  P_BOM_BS_ID     varchar2,
  P_MATTYPE       varchar2,
  P_SUBMATTYPE    varchar2,
  P_PRODUCT_CAT   varchar2,
  P_PROD_SUB_CAT  varchar2,
  P_SITE          varchar2,
  P_COMPONENT_ID  varchar2,
  P_SEARCH_DESC   varchar2,
  P_COMP_DESC_EN  varchar2,
  P_COMP_DESC_TH  varchar2,
  P_UOM           varchar2,
  P_USER_ROLE     varchar2,
  P_USER          varchar2,
  P_ERROR         out varchar2
) is
  v_no number := 0;
begin
  begin
    select nvl(max(to_number(no)), 0)
      into v_no
      from PROJ1_2_DML_BIZSUP_COMP_M6;
  exception
    when others then
      v_no := 0;
  end;

  update PROJ1_2_DML_BIZSUP_COMP_M6
     set SEARCH_DESCRIPTION  = P_SEARCH_DESC,
         FULL_DESCRIPTION_EN = P_COMP_DESC_EN,
         FULL_DESCRIPTION_TH = P_COMP_DESC_TH,
         SITE               = P_SITE,
         UOM                = P_UOM,
         USER_UPDATE        = P_USER,
         UPDATE_DATE        = sysdate,
         STATUS_ROW         = 'UPD'
   where trim(BIZSUP_ID) = trim(P_BOM_BS_ID)
     and trim(MATERIAL_ID_BIZSUP_COMP_M6) = trim(P_COMPONENT_ID);

  if SQL%ROWCOUNT = 0 then
    insert into PROJ1_2_DML_BIZSUP_COMP_M6(
      NO,
      MATERIAL_ID_BIZSUP_COMP_M6,
      SEARCH_DESCRIPTION,
      FULL_DESCRIPTION_EN,
      FULL_DESCRIPTION_TH,
      BIZSUP_ID,
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
      P_BOM_BS_ID,
      P_SITE,
      P_UOM,
      'INS',
      P_USER_ROLE,
      P_USER,
      sysdate,
      null,
      null
    );
  end if;

  P_ERROR := '';
exception
  when others then
    P_ERROR := sqlcode || ':' || substr(sqlerrm, 1, 200);
end;
/
