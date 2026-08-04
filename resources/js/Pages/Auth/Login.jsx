import { Head, useForm } from "@inertiajs/react";
import { useEffect } from "react";

import VisibilityOffRounded from "@mui/icons-material/VisibilityOffRounded";
import VisibilityRounded from "@mui/icons-material/VisibilityRounded";

import LoadingButton from "@mui/lab/LoadingButton";
import Grid from "@mui/material/Grid";
import IconButton from "@mui/material/IconButton";
import InputAdornment from "@mui/material/InputAdornment";
import Stack from "@mui/material/Stack";
import Typography from "@mui/material/Typography";

import TextInput from "@/Components/TextInput";
import { useBoolean } from "@/hooks/use-boolean";
import { LoginForm, LoginScreenImage } from "@/theme/styles";

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, get, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset("password");
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();

        post(route("login"));
    };

    const password = useBoolean();

    return (
        <>
            <Head title="Log in" />

            <Grid container component="main" sx={{ height: "100vh" }}>
                <LoginScreenImage
                    item
                    xs={false}
                    sm={false}
                    md={8}
                    lg={8}
                    xl={9}
                />
                <LoginForm item xs={12} sm={12} md={4} lg={4} xl={3}>
                    <Stack
                        spacing={2}
                        minHeight="100%"
                        justifyContent="space-between"
                    >
                        <Stack alignItems="center">
                            <img
                                src="./patterns-logo.svg"
                                alt={import.meta.env.VITE_APP_TITLE}
                                width={180}
                            />
                        </Stack>
                        <Stack
                            direction="column"
                            height="100%"
                            justifyContent="center"
                            spacing={2}
                        >
                            <Stack
                                alignContent="center"
                                justifyContent="center"
                            >
                                <Typography
                                    textAlign="center"
                                    variant="h4"
                                    component="h1"
                                    fontWeight="800"
                                >
                                    Welcome Back!
                                </Typography>
                                <Typography
                                    textAlign="center"
                                    color="text.secondary"
                                    variant="body2"
                                >
                                    Login with your email and password to
                                    continue
                                </Typography>
                            </Stack>
                            <Stack
                                spacing={2}
                                component="form"
                                noValidate
                                autoComplete="off"
                            >
                                <TextInput
                                    required
                                    autoFocus
                                    id="email"
                                    label="Email Address"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData("email", e.target.value)
                                    }
                                    error={errors.email}
                                    helperText={errors.email}
                                />
                                <TextInput
                                    required
                                    id="password"
                                    label="Password"
                                    type={password.value ? "text" : "password"}
                                    value={data.password}
                                    onChange={(e) =>
                                        setData("password", e.target.value)
                                    }
                                    error={errors.password}
                                    helperText={errors.password}
                                    InputProps={{
                                        endAdornment: (
                                            <InputAdornment position="end">
                                                <IconButton
                                                    edge="end"
                                                    onClick={password.onToggle}
                                                >
                                                    {password.value ? (
                                                        <VisibilityOffRounded fontSize="small" />
                                                    ) : (
                                                        <VisibilityRounded fontSize="small" />
                                                    )}
                                                </IconButton>
                                            </InputAdornment>
                                        ),
                                    }}
                                />
                                <LoadingButton
                                    type="submit"
                                    variant="contained"
                                    loading={processing}
                                    onClick={submit}
                                >
                                    Login
                                </LoadingButton>
                            </Stack>
                        </Stack>
                        <Typography
                            align="center"
                            color="text.secondary"
                            variant="body2"
                        >
                            {`© ${new Date().getFullYear()} ${
                                import.meta.env.VITE_APP_NAME
                            }. All rights reserved.`}
                        </Typography>
                    </Stack>
                </LoginForm>
            </Grid>
        </>
    );
}
