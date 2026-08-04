import TextInput from "@/Components/TextInput";
import SnackBar from "@/Layouts/components/snack-bar";
import { Link, useForm, usePage } from "@inertiajs/react";
import { LoadingButton } from "@mui/lab";
import { Grid } from "@mui/material";

export default function UpdateProfileInformation({ mustVerifyEmail, status }) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
        });

    const submit = (e) => {
        e.preventDefault();

        patch(route("profile.update"));
    };

    return (
        <Grid container spacing={2} mt={2} alignItems="center">
            <Grid item xs={3}>
                <TextInput
                    id="name"
                    label="Name"
                    value={data.name}
                    onChange={(e) => setData("name", e.target.value)}
                    required
                    isFocused
                    autoComplete="name"
                    error={errors.name}
                    helperText={errors.name}
                />
            </Grid>

            <Grid item xs={3}>
                <TextInput
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData("email", e.target.value)}
                    required
                    autoComplete="username"
                    label="Email"
                    error={errors.email}
                    helperText={errors.email}
                />
            </Grid>

            {mustVerifyEmail && user.email_verified_at === null && (
                <div>
                    <p className="text-sm mt-2 text-gray-800">
                        Your email address is unverified.
                        <Link
                            href={route("verification.send")}
                            method="post"
                            as="button"
                            className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Click here to re-send the verification email.
                        </Link>
                    </p>

                    {status === "verification-link-sent" && (
                        <div className="mt-2 font-medium text-sm text-green-600">
                            A new verification link has been sent to your email
                            address.
                        </div>
                    )}
                </div>
            )}

            <Grid item xs={3}>
                <LoadingButton
                    variant="contained"
                    loading={processing}
                    onClick={submit}
                >
                    Save
                </LoadingButton>
            </Grid>
            <SnackBar open={recentlySuccessful} message="Profile saved !!" />
        </Grid>
    );
}
