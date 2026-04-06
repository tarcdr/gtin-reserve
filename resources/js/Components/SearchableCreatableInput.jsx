import { useId } from 'react';
import TextInput from '@/Components/TextInput';

export default function SearchableCreatableInput({
  id,
  value,
  onChange,
  options = [],
  placeholder = '',
  className = '',
  ...props
}) {
  const generatedId = useId();
  const listId = `${id || generatedId}-options`;
  const uniqueOptions = options.filter(
    (item, index, array) => item?.code && array.findIndex((candidate) => candidate?.code === item.code) === index
  );

  return (
    <>
      <TextInput
        {...props}
        id={id}
        list={listId}
        className={className}
        value={value}
        onChange={(e) => onChange?.(e.target.value, e)}
        placeholder={placeholder}
      />
      <datalist id={listId}>
        {uniqueOptions.map((option) => (
          <option
            key={`${listId}-${option.code}`}
            value={option.code}
            label={option.label ? `${option.code} - ${option.label}` : option.code}
          />
        ))}
      </datalist>
    </>
  );
}
