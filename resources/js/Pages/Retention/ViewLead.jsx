import CallDialog from "@/Call/CallDialog";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { formatDate, formatDateTime } from "@/utils/date-time-formatters";
import ClientLeadTable from "./ClientLeadTable";
import DispositionLeadTable from "./DispositionLeadTable";

import { Head } from "@inertiajs/react";
import {
    ApartmentRounded,
    BallotRounded,
    BusinessRounded,
    DraftsTwoTone,
    PeopleAltRounded,
} from "@mui/icons-material";
import { TabContext, TabPanel } from "@mui/lab";
import {
    Avatar,
    Box,
    Card,
    CardContent,
    CardHeader,
    Grid,
    Link,
    List,
    ListItem,
    ListItemButton,
    ListItemText,
    Stack,
    Tab,
    Tabs,
    Typography,
} from "@mui/material";
import { useState } from "react";

const ViewLead = ({ auth, retention }) => {
    const { data } = retention;

    const [value, setValue] = useState("retention");

    const handleChange = (event, newValue) => {
        setValue(newValue);
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="View Retention" />
            <MainContentTemplate
                title="View Retention"
                subtitle="View retention from here"
                button="Go back"
                href={route("retention.index")}
            >
                <Grid item sx={{ width: "100%" }}>
                    <TabContext value={value}>
                        <Box sx={{ borderBottom: 1, borderColor: "divider" }}>
                            <Tabs
                                value={value}
                                onChange={handleChange}
                                aria-label="Retention Details Tabs"
                            >
                                <Tab
                                    label="Retention"
                                    value="retention"
                                    icon={<ApartmentRounded fontSize="small" />}
                                    iconPosition="start"
                                />
                                <Tab
                                    label="Clients"
                                    value="clients"
                                    icon={<PeopleAltRounded fontSize="small" />}
                                    iconPosition="start"
                                />
                                <Tab
                                    label="Dispositions"
                                    value="dispositions"
                                    icon={<BallotRounded fontSize="small" />}
                                    iconPosition="start"
                                />
                            </Tabs>
                        </Box>
                        <TabPanel value="retention" sx={{ px: 0 }}>
                            <RetentionDetailsComponent data={data} />
                        </TabPanel>
                        <TabPanel value="clients">
                            <ClientLeadTable
                                clients={data.clients}
                                companyId={data.id}
                                companyName={data.name}
                            />
                        </TabPanel>
                        <TabPanel value="dispositions">
                            <DispositionLeadTable
                                dispositions={data.disposition_history}
                            />
                        </TabPanel>
                    </TabContext>
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default ViewLead;

const RetentionDetailsComponent = ({ data }) => {
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
                                    bgcolor: "primary.main",
                                }}
                            >
                                <BusinessRounded />
                            </Avatar>
                        }
                        title={data.name}
                        subheader={formatDate(data.created_at)}
                    />
                    <CardContent sx={{ p: 0, paddingBottom: "0 !important" }}>
                        <List dense disablePadding>
                            <ListItem divider>
                                <ListItemText
                                    primary="Last US Order Date"
                                    secondary={
                                        formatDateTime(
                                            data.last_order_us_date
                                        ) || "N/A"
                                    }
                                />
                            </ListItem>
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
                                    Details
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
                                    <EmailListComponent
                                        retentionEmail={data.email}
                                    />
                                    <PhoneListComponent
                                        retentionPhone={data.phone}
                                        assignToId={data.assign_to}
                                        name={data.name}
                                        companyid={data.id}
                                    />
                                </Grid>
                            </Grid>
                            <Grid item xs={12}>
                                <Typography variant="body1" component="p">
                                    <strong>Timezone: </strong>
                                    {data.timezone || "N/A"}
                                </Typography>
                            </Grid>
                            <Grid item xs={12}>
                                <Typography variant="body1" component="p">
                                    <strong>Address: </strong>
                                    {data.address || "N/A"}
                                </Typography>
                            </Grid>
                            <Grid item xs={12} mt={5}>
                                <Stack
                                    direction="row"
                                    spacing={1}
                                    sx={{
                                        display: "flex",
                                        alignItems: "center",
                                        justifyContent: "center",
                                        gap: 2,
                                    }}
                                >
                                    <Typography variant="body1" component="p">
                                        <strong>Created at: </strong>
                                        <Box
                                            component="span"
                                            color="success.main"
                                        >
                                            {formatDateTime(data.created_at) ||
                                                "-"}
                                        </Box>
                                    </Typography>
                                    <Typography variant="body1" component="p">
                                        <strong>Updated at: </strong>
                                        <Box
                                            component="span"
                                            color="success.main"
                                        >
                                            {formatDateTime(data.updated_at) ||
                                                "-"}
                                        </Box>
                                    </Typography>
                                </Stack>
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>
            </Grid>
        </Grid>
    );
};

const EmailListComponent = ({ retentionEmail }) => {
    return (
        <Grid item xs={6}>
            {retentionEmail.length > 0 ? (
                retentionEmail.map((email, index) => (
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

const PhoneListComponent = ({
    retentionPhone,
    assignToId,
    name,
    retentionid,
}) => {
    return (
        <Grid item xs={6}>
            {retentionPhone.length > 0 ? (
                retentionPhone.map((field, index) => (
                    <CallDialog
                        key={index}
                        phone={field}
                        name={name}
                        id={retentionid}
                        assignedUserId={assignToId}
                        updatePropName={"retentions"}
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
