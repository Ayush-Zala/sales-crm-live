import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";
import { Card, CardContent, Grid, Stack, Typography } from "@mui/material";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm";
import ZoomDetailsForm from "./Partials/ZoomDetailsForm";

export default function Edit({
    auth,
    mustVerifyEmail,
    status,
    zoomApiDetails,
}) {
    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Profile" />

            <MainContentTemplate
                title="Profile details and settings"
                subtitle="Profile details and settings"
                button="Go back"
                onClick={() => window.history.back()}
            >
                <Grid item xs={12}>
                    <Card variant="outlined">
                        <CardContent>
                            <Stack spacing={2}>
                                <Stack pb={1}>
                                    <Typography variant="h5">
                                        Edit Profile
                                    </Typography>
                                    <Typography
                                        variant="caption"
                                        component="small"
                                        color="text.secondary"
                                    >
                                        Update your account's profile
                                        information and email address.
                                    </Typography>
                                    <UpdateProfileInformationForm
                                        mustVerifyEmail={mustVerifyEmail}
                                        status={status}
                                    />
                                </Stack>
                            </Stack>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12}>
                    <Card variant="outlined">
                        <CardContent>
                            <Stack spacing={2}>
                                <Stack pb={1}>
                                    <Typography variant="h5">
                                        Update Password
                                    </Typography>
                                    <Typography
                                        variant="caption"
                                        component="small"
                                        color="text.secondary"
                                    >
                                        Manage your password here.
                                    </Typography>
                                    <UpdatePasswordForm />
                                </Stack>
                            </Stack>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12}>
                    <Card variant="outlined">
                        <CardContent>
                            <Stack spacing={2}>
                                <Stack pb={1}>
                                    <Typography variant="h5">
                                        Zoom Details
                                    </Typography>
                                    <Typography
                                        variant="caption"
                                        component="small"
                                        color="text.secondary"
                                    >
                                        Manage your zoom details here.
                                    </Typography>
                                    <ZoomDetailsForm
                                        zoomApiDetails={zoomApiDetails}
                                    />
                                </Stack>
                            </Stack>
                        </CardContent>
                    </Card>
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}
