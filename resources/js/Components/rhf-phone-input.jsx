import { Controller } from "react-hook-form";
import { PhoneInput } from "./phone-input";

// ----------------------------------------------------------------------

export function RHFPhoneInput({
    name,
    control,
    setValue,
    helperText,
    endAdornment,
    ...other
}) {
    return (
        <Controller
            name={name}
            control={control}
            render={({ field, fieldState: { error } }) => (
                <PhoneInput
                    {...field}
                    fullWidth
                    value={field.value}
                    onChange={(newValue) =>
                        setValue(name, newValue, { shouldValidate: true })
                    }
                    error={!!error}
                    helperText={error ? error?.message : helperText}
                    endAdornment={endAdornment}
                    {...other}
                />
            )}
        />
    );
}
