import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';

export default function BusinessSupplyDetail({
  mode = 'create',
  values = {},
  errors = {},
  uoms = [],
  sourceLabel = '',
  isEditing = false,
  showActions = true,
  onChange,
  onCancel,
  onSave,
  onEdit,
  onDelete,
}) {
  const isExisting = mode === 'existing';
  const isReadOnly = isExisting && !isEditing;
  const readOnlyClass = 'mt-1 block w-full border-gray-300 rounded-md bg-gray-100';
  const editableTextClass = 'mt-1 block w-full border-gray-300 rounded-md';

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <InputLabel htmlFor="bomBsId" value="BOM ID for Business Supply" />
          <TextInput
            id="bomBsId"
            className={readOnlyClass}
            value={values.bomBsId || ''}
            disabled
          />
          <InputError className="mt-2" message={errors.bomBsId} />
        </div>
        <div />
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <InputLabel htmlFor="bsId" value="Business Supply ID" />
          <TextInput
            id="bsId"
            className={readOnlyClass}
            value={values.bsId || ''}
            disabled
          />
          <InputError className="mt-2" message={errors.bsId} />
        </div>
        <div>
          <InputLabel htmlFor="searchDesc" value="Search Description" />
          <TextInput
            id="searchDesc"
            className={isReadOnly ? readOnlyClass : editableTextClass}
            value={values.searchDesc || ''}
            disabled={isReadOnly}
            onChange={(e) => onChange?.('searchDesc', e.target.value)}
          />
          <InputError className="mt-2" message={errors.searchDesc} />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <InputLabel htmlFor="compDescEn" value="Full Description (EN)" />
          <TextInput
            id="compDescEn"
            className={isReadOnly ? readOnlyClass : editableTextClass}
            value={values.compDescEn || ''}
            disabled={isReadOnly}
            onChange={(e) => onChange?.('compDescEn', e.target.value)}
          />
          <InputError className="mt-2" message={errors.compDescEn} />
        </div>
        <div>
          <InputLabel htmlFor="compDescTh" value="Full Description (TH)" />
          <TextInput
            id="compDescTh"
            className={isReadOnly ? readOnlyClass : editableTextClass}
            value={values.compDescTh || ''}
            disabled={isReadOnly}
            onChange={(e) => onChange?.('compDescTh', e.target.value)}
          />
          <InputError className="mt-2" message={errors.compDescTh} />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <InputLabel htmlFor="uom" value="UOM" />
          {isReadOnly ? (
            <TextInput
              id="uom"
              className={readOnlyClass}
              value={values.uom || ''}
              disabled
            />
          ) : (
            <select
              id="uom"
              className="mt-1 block w-full border-gray-300 rounded-md"
              value={values.uom || ''}
              onChange={(e) => onChange?.('uom', e.target.value)}
            >
              <option value="">---- Select UOM ----</option>
              {uoms.map((item) => (
                <option key={`uom-${item.value}`} value={item.value}>
                  {item.label}
                </option>
              ))}
            </select>
          )}
          <InputError className="mt-2" message={errors.uom} />
        </div>
        <div>
          <InputLabel value="Business Supply Source" />
          <TextInput
            className={readOnlyClass}
            value={sourceLabel || values.sourceLabel || ''}
            disabled
          />
        </div>
      </div>

      {showActions ? (
        <div className="flex flex-wrap justify-center gap-3">
          {isExisting ? (
            <>
              <SecondaryButton type="button" onClick={onCancel}>
                Cancel
              </SecondaryButton>
              <PrimaryButton type="button" onClick={onEdit} disabled={!onEdit}>
                Edit
              </PrimaryButton>
              <DangerButton type="button" onClick={onDelete} disabled={!onDelete}>
                Delete
              </DangerButton>
            </>
          ) : (
            <>
              <SecondaryButton type="button" onClick={onCancel}>
                Cancel
              </SecondaryButton>
              <PrimaryButton type="button" onClick={onSave}>
                SAVE BS
              </PrimaryButton>
            </>
          )}
        </div>
      ) : null}
    </div>
  );
}
