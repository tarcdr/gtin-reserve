create or replace NONEDITIONABLE procedure proj1_2_save_bom_semi_l1(
  P_MATTYPE                    varchar2,
  P_SUB_MATTYPE                varchar2,
  P_SEMI_FG_L1_BOM_ID          varchar2,
  P_SEMI_FG_L1_BOM_DESC        varchar2,
  P_SEMI_FG_L1_ID              varchar2,
  P_SEMI_FG_L1_ID_DESC         varchar2,
  P_SEMI_FG_L1_ID_FULL_DESC_EN varchar2,
  P_SEMI_FG_L1_ID_FULL_DESC_TH varchar2,
  P_FG_BOM_ID                  varchar2,
  P_MATERIAL_ID_FG_1           varchar2,
  P_SEMI_FG_L2_BOM_ID          varchar2,
  P_SEMI_FG_L2_ID              varchar2,
  P_SITE                       varchar2,
  P_UOM_SEMI_FG_L1ID           varchar2,
  P_STATUS_ROW                 varchar2,
  P_USER_ROLE                  varchar2,
  P_USER_LOGIN                 varchar2,
  P_ERROR              out     varchar2
)
is
  v_bom_count number := 0;
  v_id_count  number := 0;
  v_no        number := 0;
begin
  select count(*)
    into v_bom_count
    from PROJ1_2_DML_SEMI_L1_BOM
   where trim(SEMI_FG_LV1_BOM_ID) = trim(P_SEMI_FG_L1_BOM_ID);

  select count(*)
    into v_id_count
    from PROJ1_2_DML_SEMI_L1_ID
   where trim(SEMI_FG_LV1_ID) = trim(P_SEMI_FG_L1_ID);

  if v_bom_count > 0 then
    update PROJ1_2_DML_SEMI_L1_BOM
       set DESC_SEMI_FG_LV1_BOM_ID = P_SEMI_FG_L1_BOM_DESC,
           FG_BOM_ID               = P_FG_BOM_ID,
           MATERIAL_ID_FG_1        = P_MATERIAL_ID_FG_1,
           SEMI_FG_LV2_BOM_ID      = P_SEMI_FG_L2_BOM_ID,
           SEMI_FG_LV2_ID          = P_SEMI_FG_L2_ID,
           SITE                    = P_SITE,
           STATUS_ROW              = P_STATUS_ROW,
           USER_ROLE               = P_USER_ROLE,
           USER_UPDATE             = P_USER_LOGIN,
           UPDATE_DATE             = sysdate
     where trim(SEMI_FG_LV1_BOM_ID) = trim(P_SEMI_FG_L1_BOM_ID);
  else
    begin
      select nvl(max(to_number(NO)), 0)
        into v_no
        from PROJ1_2_DML_SEMI_L1_BOM;
    exception
      when others then
        v_no := 0;
    end;

    insert into PROJ1_2_DML_SEMI_L1_BOM(
      NO,
      SEMI_FG_LV1_BOM_ID,
      DESC_SEMI_FG_LV1_BOM_ID,
      FG_BOM_ID,
      MATERIAL_ID_FG_1,
      SEMI_FG_LV2_BOM_ID,
      SEMI_FG_LV2_ID,
      SITE,
      STATUS_ROW,
      USER_ROLE,
      USER_CREATE,
      CREATE_DATE,
      USER_UPDATE,
      UPDATE_DATE
    ) values (
      v_no + 1,
      P_SEMI_FG_L1_BOM_ID,
      P_SEMI_FG_L1_BOM_DESC,
      P_FG_BOM_ID,
      P_MATERIAL_ID_FG_1,
      P_SEMI_FG_L2_BOM_ID,
      P_SEMI_FG_L2_ID,
      P_SITE,
      P_STATUS_ROW,
      P_USER_ROLE,
      P_USER_LOGIN,
      sysdate,
      null,
      null
    );
  end if;

  if v_id_count > 0 then
    update PROJ1_2_DML_SEMI_L1_ID
       set DESC_SEMI_FG_LV1_ID      = P_SEMI_FG_L1_ID_DESC,
           FULL_DESC_SEMI_FG_LV1_EN = P_SEMI_FG_L1_ID_FULL_DESC_EN,
           FULL_DESC_SEMI_FG_LV1_TH = P_SEMI_FG_L1_ID_FULL_DESC_TH,
           MATTYPE_SEMI_FG_L1ID     = P_MATTYPE,
           SUB_MATTYPE_SEMI_FG_L1ID = P_SUB_MATTYPE,
           UOM_SEMI_FG_L1ID         = P_UOM_SEMI_FG_L1ID,
           FG_BOM_ID                = P_FG_BOM_ID,
           MATERIAL_ID_FG_1         = P_MATERIAL_ID_FG_1,
           SEMI_FG_LV2_BOM_ID       = P_SEMI_FG_L2_BOM_ID,
           SEMI_FG_LV2_ID           = P_SEMI_FG_L2_ID,
           SEMI_FG_LV1_BOM_ID       = P_SEMI_FG_L1_BOM_ID,
           SITE                     = P_SITE,
           STATUS_ROW               = P_STATUS_ROW,
           USER_ROLE                = P_USER_ROLE,
           USER_UPDATE              = P_USER_LOGIN,
           UPDATE_DATE              = sysdate
     where trim(SEMI_FG_LV1_ID) = trim(P_SEMI_FG_L1_ID);
  else
    begin
      select nvl(max(to_number(NO)), 0)
        into v_no
        from PROJ1_2_DML_SEMI_L1_ID;
    exception
      when others then
        v_no := 0;
    end;

    insert into PROJ1_2_DML_SEMI_L1_ID(
      NO,
      SEMI_FG_LV1_ID,
      DESC_SEMI_FG_LV1_ID,
      FULL_DESC_SEMI_FG_LV1_EN,
      FULL_DESC_SEMI_FG_LV1_TH,
      MATTYPE_SEMI_FG_L1ID,
      SUB_MATTYPE_SEMI_FG_L1ID,
      UOM_SEMI_FG_L1ID,
      FG_BOM_ID,
      MATERIAL_ID_FG_1,
      SEMI_FG_LV2_BOM_ID,
      SEMI_FG_LV2_ID,
      SEMI_FG_LV1_BOM_ID,
      SITE,
      STATUS_ROW,
      USER_ROLE,
      USER_CREATE,
      CREATE_DATE,
      USER_UPDATE,
      UPDATE_DATE
    ) values (
      v_no + 1,
      P_SEMI_FG_L1_ID,
      P_SEMI_FG_L1_ID_DESC,
      P_SEMI_FG_L1_ID_FULL_DESC_EN,
      P_SEMI_FG_L1_ID_FULL_DESC_TH,
      P_MATTYPE,
      P_SUB_MATTYPE,
      P_UOM_SEMI_FG_L1ID,
      P_FG_BOM_ID,
      P_MATERIAL_ID_FG_1,
      P_SEMI_FG_L2_BOM_ID,
      P_SEMI_FG_L2_ID,
      P_SEMI_FG_L1_BOM_ID,
      P_SITE,
      P_STATUS_ROW,
      P_USER_ROLE,
      P_USER_LOGIN,
      sysdate,
      null,
      null
    );
  end if;

  commit;
  P_ERROR := '';
exception
  when others then
    rollback;
    P_ERROR := 'ERR-002: ' || sqlerrm;
end;
/
