import { Head, router, usePage } from "@inertiajs/react";
import { BusinessRounded, DraftsTwoTone } from "@mui/icons-material";
import {
    Avatar,
    Box,
    Card,
    CardContent,
    CardHeader,
    Chip,
    Grid,
    Link,
    List,
    ListItem,
    ListItemButton,
    ListItemText,
    Stack,
    Switch,
    Typography,
} from "@mui/material";
import { confirm } from "material-ui-confirm";
import { useState } from "react";
import toast from "react-hot-toast";

import CallDialog from "@/Call/CallDialog";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { hasPermission } from "@/utils/AccessManager";
import { formatDate, formatDateTime } from "@/utils/date-time-formatters";
import ClientsTable from "./ClientsTable";
import DispositionsTable from "./DispositionsTable";

const ViewAccount = ({ auth, company }) => {
    const { data } = company;

    // console.log(data);

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="View Account" />
            <MainContentTemplate
                title="View account"
                subtitle="View Account details here"
                button="Go back"
                onClick={() => window.history.back()}
                secondButton="Edit"
                secondButtonHref={route("account.edit", data?.id)}
            >
                <Grid item sx={{ width: "100%" }}>
                    <CompanyComponent data={data} />
                    <DispositionsTable
                        dispositions={data.disposition_history}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default ViewAccount;

const CompanyComponent = ({ data }) => {
    const { auth } = usePage().props;
    const hasCompanyBlacklistPermission = hasPermission(
        auth,
        "Can Blacklist Company"
    );

    return (
        <Grid item container columns={12} spacing={2} xs={12}>
            <Grid item lg={4} md={6} xs={12}>
                <Card>
                    <CardHeader
                        avatar={
                            <Avatar
                                aria-label={data.name}
                                variant="rounded"
                                sx={{
                                    bgcolor: data.blacklisted
                                        ? "error.main"
                                        : "primary.main",
                                }}
                            >
                                <BusinessRounded />
                            </Avatar>
                        }
                        action={
                            <Chip
                                size="small"
                                label={
                                    data.blacklisted ? "Blacklisted" : "Active"
                                }
                                color={data.blacklisted ? "error" : "success"}
                            />
                        }
                        title={data.name}
                        subheader={formatDate(data.created_at)}
                    />
                    <CardContent sx={{ p: 0, paddingBottom: "0 !important" }}>
                        <List dense disablePadding>
                            <ListItem divider>
                                <ListItemText
                                    primary="Industry"
                                    secondary={data.industry || "N/A"}
                                />
                            </ListItem>
                            <ListItem divider>
                                <ListItemText
                                    primary="Source"
                                    secondary={data.source || "N/A"}
                                />
                            </ListItem>
                            <ListItemButton
                                divider
                                component="a"
                                href={data.website}
                                target="_blank"
                            >
                                <ListItemText
                                    primary="Website"
                                    secondary={data.website || "N/A"}
                                />
                            </ListItemButton>
                            <ListItem divider>
                                <ListItemText
                                    primary="Assign By"
                                    secondary={data.assign_by || "N/A"}
                                />
                            </ListItem>
                            <ListItem divider>
                                <ListItemText
                                    primary="Assign To"
                                    secondary={data.assign_to || "N/A"}
                                />
                            </ListItem>
                            <ListItem divider>
                                <ListItemText
                                    primary="Timezone"
                                    secondary={data.timezone || "N/A"}
                                />
                            </ListItem>
                            <ListItem divider>
                                <ListItemText
                                    primary="Status"
                                    secondary={
                                        data.blacklisted
                                            ? "Blacklisted"
                                            : "Active"
                                    }
                                />
                                {hasCompanyBlacklistPermission && (
                                    <IsActiveSwitch company={data} />
                                )}
                            </ListItem>
                            <ListItem>
                                <ListItemText
                                    primary="Updated at"
                                    secondary={
                                        formatDateTime(data.updated_at) || "N/A"
                                    }
                                    secondaryTypographyProps={{
                                        color: "success.main",
                                    }}
                                />
                            </ListItem>
                        </List>
                    </CardContent>
                </Card>
            </Grid>
            <Grid item lg={8} md={6} xs={12}>
                <Card>
                    <CardContent>
                        <Grid container spacing={2} columns={12}>
                            <Grid item xs={12}>
                                <Typography variant="h6" component="h6">
                                    Other Details
                                </Typography>
                            </Grid>
                            <Grid item xs={12}>
                                <Typography variant="body1" component="p">
                                    <strong>Type: </strong>
                                    {data.vendor_type || "N/A"}
                                </Typography>
                            </Grid>
                            <Grid item xs={12}>
                                <Grid item container spacing={2} columns={12}>
                                    {/* Email list and phone list */}
                                    <EmailListComponent
                                        companyEmail={data.email}
                                    />
                                    <PhoneListComponent
                                        companyPhone={data.phone}
                                        assignToId={data.assign_to_id}
                                        name={data.name}
                                        companyid={data.id}
                                    />
                                </Grid>
                            </Grid>
                            <Grid item xs={12}>
                                <Typography variant="body1" component="p">
                                    <strong>Address: </strong>
                                    {data.address || "N/A"}
                                </Typography>
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>
                <ClientsTable
                    clients={data.clients}
                    companyId={data.id}
                    companyName={data.name}
                />
            </Grid>
        </Grid>
    );
};

const EmailListComponent = ({ companyEmail }) => {
    return (
        <Grid item xs={6}>
            {companyEmail.length > 0 ? (
                companyEmail.map((email, index) => (
                    <Stack key={index} direction="row" spacing={1}>
                        <DraftsTwoTone fontSize="small" color="success" />
                        <Typography
                            key={index}
                            variant="body2"
                            color="primary.main"
                            component={Link}
                            href={`mailto:${email}`}
                            sx={{ textDecoration: "none" }}
                        >
                            {email}
                        </Typography>
                    </Stack>
                ))
            ) : (
                <Typography variant="body2" color="error" component="p">
                    No email address found
                </Typography>
            )}
        </Grid>
    );
};

const PhoneListComponent = ({ companyPhone, assignToId, name, companyid }) => {
    return (
        <Grid item xs={6}>
            {companyPhone.length > 0 ? (
                companyPhone.map((field, index) => (
                    <CallDialog
                        key={index}
                        phone={field}
                        name={name}
                        id={companyid}
                        assignedUserId={assignToId}
                        updatePropName={"accounts"}
                    />
                ))
            ) : (
                <Typography variant="body2" color="error" component="p">
                    No phone number found
                </Typography>
            )}
        </Grid>
    );
};

const IsActiveSwitch = ({ company }) => {
    const [blacklisted, setBlacklisted] = useState(company.blacklisted);

    const handleChange = async (event) => {
        await confirm({
            title: `${
                blacklisted
                    ? "Blacklist and Unassign user from this account"
                    : "Whitelist"
            } ${company.name}!`,
            description: `Are you sure you want to ${
                blacklisted
                    ? "blacklist and Unassign user from this account"
                    : "whitelist"
            } "${company.name}"?`,
            confirmationText: "Yes",
            cancellationText: "No",
            confirmationButtonProps: {
                color: blacklisted ? "error" : "success",
            },
        }).then(() => {
            // Update the company's status in the database
            router.patch(
                route("account.toggleblacklist", { id: company.id }),
                {},
                {
                    onSuccess: (response) => {
                        if (response.props.flash.error) {
                            toast.error(response.props.flash.error);
                            return;
                        }

                        setBlacklisted(!event.target.checked);
                        toast.success(response.props.flash.success);
                    },
                    onError: (error) => {
                        console.error(error);
                        toast.error("Something went wrong! Please try again.");
                    },
                }
            );
        });
    };

    return (
        <Switch
            checked={blacklisted}
            onChange={handleChange}
            id={`toggle-company-status-${company.id}`}
            color={blacklisted ? "error" : "success"}
        />
    );
};
