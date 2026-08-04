import TextInput from "@/Components/TextInput";
import { useForm } from "@inertiajs/react";
import { LoadingButton } from "@mui/lab";
import { Grid } from "@mui/material";
import { useRef } from "react";
import toast from "react-hot-toast";

export default function UpdatePasswordForm() {
    const passwordInput = useRef();

    const { data, setData, errors, put, reset, processing } = useForm({
        password: "",
        password_confirmation: "",
    });

    const updatePassword = (e) => {
        e.preventDefault();

        put(route("password.update"), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                toast.success("Password updated !!");
            },
            onError: (errors) => {
                if (errors.password) {
                    reset("password", "password_confirmation");
                    passwordInput.current.focus();
                }
            },
        });
    };

    return (
        <Grid container spacing={2} mt={2} alignItems="center">
            <Grid item xs={3}>
                <TextInput
                    id="password"
                    label="password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData("password", e.target.value)}
                    required
                    isFocused
                    autoComplete="password"
                    error={errors.password}
                    helperText={errors.password}
                />
            </Grid>

            <Grid item xs={3}>
                <TextInput
                    id="password_confirmation"
                    label="Password confirmation"
                    type="password"
                    value={data.password_confirmation}
                    onChange={(e) =>
                        setData("password_confirmation", e.target.value)
                    }
                    required
                    isFocused
                    autoComplete="password_confirmation"
                    error={errors.password_confirmation}
                    helperText={errors.password_confirmation}
                />
            </Grid>

            <Grid item xs={3}>
                <LoadingButton
                    variant="contained"
                    loading={processing}
                    onClick={updatePassword}
                >
                    Save
                </LoadingButton>
            </Grid>
        </Grid>
    );
}
