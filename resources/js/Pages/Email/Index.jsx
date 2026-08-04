import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Grid } from "@mui/material";

export default function Index({ auth }) {
    return (
        <AuthenticatedLayout user={auth.user}>
            <MainContentTemplate
                title="Email"
                subtitle="Email data will be shown here"
            >
                <Grid item spacing={2}>
                    Under Development
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}
