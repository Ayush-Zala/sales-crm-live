import { TextField } from "@mui/material";
import { forwardRef, useEffect, useRef } from "react";

export default forwardRef(function TextInput(
    { type = "text", isFocused = false, error, helperText, ...props },
    ref
) {
    const input = ref ? ref : useRef();

    useEffect(() => {
        if (isFocused) {
            input.current.focus();
        }
    }, []);

    return (
        <TextField
            {...props}
            type={type}
            ref={input}
            error={error}
            helperText={helperText}
        />
    );
});
