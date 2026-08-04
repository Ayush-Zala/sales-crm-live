import {
    Card,
    CardContent,
    Typography,
    Grid,
    Divider,
    Stack,
    Link,
} from "@mui/material";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Fragment } from "react";
import ClientLeadTable from "./ClientLeadTable";
import CallDialog from "@/Call/CallDialog";
import { DraftsTwoTone } from "@mui/icons-material";
import DispositionLeadTable from "./DispositionLeadTable";

const ViewLead = ({ auth, details }) => {
    const {
        leadInfo,
        leadEmail,
        leadPhone,
        countries,
        clientInfo,
        leadDispositions,
    } = details;

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="View Lead" />
            <MainContentTemplate
                title="View Lead"
                subtitle="View Lead from here"
                button="Go back"
                href={route("lead.index")}
            >
                <Grid item container xs={12} spacing={2}>
                    <Grid
                        item
                        container
                        xs={12}
                        spacing={1}
                        sx={{ height: "400px" }}
                    >
                        <Grid item xs={6}>
                            <CompanyInformationComponent
                                leadInfo={leadInfo[0]}
                            />
                        </Grid>
                        <Grid item xs={6}>
                            <DetailsComponent leadInfo={leadInfo[0]} />
                        </Grid>
                    </Grid>
                    <Grid item container spacing={1} xs={12}>
                        <Grid item xs={6}>
                            <PhoneListComponent
                                leadPhone={leadPhone}
                                name={leadInfo.name}
                            />
                        </Grid>
                        <Grid item xs={6}>
                            <EmailListComponent leadEmail={leadEmail} />
                        </Grid>
                    </Grid>
                    <Grid item container xs={12}>
                        <Grid item container xs={12}>
                            <ClientLeadTable clients={clientInfo} />
                            <DispositionLeadTable
                                dispositions={leadDispositions}
                            />
                        </Grid>
                    </Grid>
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default ViewLead;

const CompanyInformationComponent = ({ leadInfo }) => {
    return (
        <Fragment>
            <Card sx={{ borderTop: 3, borderTopColor: "primary.main" }}>
                <CardContent>
                    <Grid item xs={12}>
                        <Typography variant="h6">
                            Company Information
                        </Typography>
                    </Grid>
                    <Grid container spacing={2} md={6} xs={12}>
                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="body1"
                                    color="textSecondary"
                                >
                                    Company Name:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.company_name || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>
                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="body1"
                                    color="textSecondary"
                                >
                                    Fax No:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.fax || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>
                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="body1"
                                    color="textSecondary"
                                >
                                    Website:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.website || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>

                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="body1"
                                    color="textSecondary"
                                >
                                    Industry:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.industry || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>

                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="subtitle1"
                                    color="textSecondary"
                                >
                                    Assigned To:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.assignTo || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>

                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="subtitle1"
                                    color="textSecondary"
                                >
                                    Description:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.description || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>
                    </Grid>
                </CardContent>
            </Card>
        </Fragment>
    );
};

const PhoneListComponent = ({ leadPhone, name }) => {
    return (
        <Fragment>
            <Card sx={{ borderTop: 3, borderTopColor: "primary.main" }}>
                <CardContent>
                    <Grid item xs={12}>
                        <Typography variant="h6">Phone Details</Typography>
                    </Grid>

                    <Grid container spacing={2}>
                        {leadPhone.map((field, index) => (
                            <Grid item xs={12} key={field.id} spacing={2}>
                                <Grid item container xs={12} sx={{ mt: 1 }}>
                                    <Grid item xs={3}>
                                        <Typography
                                            variant="subtitle1"
                                            color="textSecondary"
                                        >
                                            Phone Type:
                                        </Typography>
                                    </Grid>
                                    <Grid item xs={3}>
                                        <Typography variant="body1">
                                            {field.type || "N/A"}
                                        </Typography>
                                    </Grid>
                                </Grid>
                                <Grid item container xs={12} sx={{ mt: 1 }}>
                                    <Grid item xs={3}>
                                        <Typography
                                            variant="subtitle1"
                                            color="textSecondary"
                                        >
                                            Phone Number:
                                        </Typography>
                                    </Grid>
                                    <Grid item xs={3}>
                                        <CallDialog
                                            phone={field.phone}
                                            name={name}
                                            apiDataRoute="lead.windowrefreshdisposition"
                                            submitDispositionRoute="lead.submitleaddisposition"
                                            historyRoute="lead.getleadcallhistory"
                                        />
                                    </Grid>
                                </Grid>
                                {index < leadPhone.length - 1 && (
                                    <Divider sx={{ my: 2 }} />
                                )}
                            </Grid>
                        ))}
                    </Grid>
                </CardContent>
            </Card>
        </Fragment>
    );
};

const EmailListComponent = ({ leadEmail }) => {
    return (
        <Fragment>
            <Card sx={{ borderTop: 3, borderTopColor: "primary.main" }}>
                <CardContent>
                    <Grid item xs={12}>
                        <Typography variant="h6">Email Details</Typography>
                    </Grid>

                    <Grid container spacing={2}>
                        {leadEmail.map((field, index) => (
                            <Grid item xs={12} key={field.id}>
                                <Grid
                                    container
                                    alignItems="center"
                                    sx={{ mt: 1 }}
                                >
                                    <Grid item xs={3}>
                                        <Typography
                                            variant="subtitle1"
                                            color="textSecondary"
                                        >
                                            Email Type:
                                        </Typography>
                                    </Grid>
                                    <Grid item xs={3}>
                                        <Typography variant="body1">
                                            {field.type || "N/A"}
                                        </Typography>
                                    </Grid>
                                </Grid>
                                <Grid
                                    container
                                    alignItems="center"
                                    sx={{ mt: 1 }}
                                >
                                    <Grid item xs={3}>
                                        <Typography
                                            variant="subtitle1"
                                            color="textSecondary"
                                        >
                                            Email Address:
                                        </Typography>
                                    </Grid>
                                    <Stack direction="row" spacing={1}>
                                        <DraftsTwoTone
                                            fontSize="small"
                                            color="success"
                                        />
                                        <Typography
                                            variant="body2"
                                            color="primary.main"
                                            component={Link}
                                            href={`mailto:${field.email}`}
                                            sx={{ textDecoration: "none" }}
                                        >
                                            {field.email}
                                        </Typography>
                                    </Stack>
                                </Grid>
                                {index < leadEmail.length - 1 && (
                                    <Divider sx={{ my: 2 }} />
                                )}
                            </Grid>
                        ))}
                    </Grid>
                </CardContent>
            </Card>
        </Fragment>
    );
};

const DetailsComponent = ({ leadInfo }) => {
    return (
        <Fragment>
            <Card
                sx={{
                    padding: "10px",
                    borderTop: 3,
                    borderTopColor: "primary.main",
                }}
            >
                <CardContent>
                    <Grid item xs={12}>
                        <Typography variant="h6">Details</Typography>
                    </Grid>

                    <Grid container spacing={2} md={6} xs={12}>
                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="subtitle1"
                                    color="textSecondary"
                                >
                                    Lead Status:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.lead_status || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>

                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="subtitle1"
                                    color="textSecondary"
                                >
                                    Lead Source:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.lead_source || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>

                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="subtitle1"
                                    color="textSecondary"
                                >
                                    Company Type:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.vendor_type || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>

                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="subtitle1"
                                    color="textSecondary"
                                >
                                    Business Type:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.business_type || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>

                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="subtitle1"
                                    color="textSecondary"
                                >
                                    Opportunity Amount:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.opportunity_amount || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>

                        <Grid item container xs={12}>
                            <Grid item xs={6}>
                                <Typography
                                    variant="subtitle1"
                                    color="textSecondary"
                                >
                                    Follow-up Date:
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="body1">
                                    {leadInfo.followup_date || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>
                    </Grid>
                </CardContent>
            </Card>
        </Fragment>
    );
};
