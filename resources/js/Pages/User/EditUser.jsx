import { Head, useForm } from "@inertiajs/react";
import { LoadingButton } from "@mui/lab";
import {
    Autocomplete,
    FormControlLabel,
    Grid,
    Paper,
    Radio,
    RadioGroup,
    Switch,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    TextField,
    Typography,
} from "@mui/material";
import toast from "react-hot-toast";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";

const EditUser = ({
    auth,
    roles,
    groupPermissions,
    reportingAuthorities,
    user,
    userPermissions,
    rolePermissions,
}) => {
    const { data, setData, patch, processing } = useForm({
        name: user.name,
        email: user.email,
        roleName: user.role,
        permissions: userPermissions,
        reportingAuthority: user.reporting_authority_id || null,
    });

    const handleSwitchChange = (id) => {
        if (data.permissions.includes(id)) {
            setData(
                "permissions",
                data.permissions.filter((p) => p !== id)
            );
        } else {
            setData("permissions", [...data.permissions, id]);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        patch(route("user.update", { user_id: user.id }), {
            onSuccess: (res) => {
                toast.success(res.props.flash.success);
            },
            onError: (error) => {
                console.log(error);
                toast.error("An error occurred. Please try again.");
            },
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Edit user" />
            <MainContentTemplate
                title="Edit user"
                subtitle="Edit user details here"
                button="Go back"
                href={route("user")}
            >
                <Grid
                    container
                    component="form"
                    item
                    xs={12}
                    spacing={2}
                    onSubmit={handleSubmit}
                >
                    <Grid item container xs={12} spacing={2}>
                        <Grid item xs={12} md={4}>
                            <TextField
                                label="Name"
                                value={data.name}
                                onChange={(e) => {
                                    setData("name", e.target.value);
                                }}
                            />
                        </Grid>
                        <Grid item xs={12} md={4}>
                            <TextField
                                label="Email"
                                value={data.email}
                                type="email"
                                onChange={(e) => {
                                    setData("email", e.target.value);
                                }}
                            />
                        </Grid>
                        <Grid item xs={12} md={4}>
                            <Autocomplete
                                label="Reporting Authorities"
                                options={reportingAuthorities}
                                value={
                                    reportingAuthorities.find(
                                        (authority) =>
                                            authority.id ===
                                            data.reportingAuthority
                                    ) || null
                                }
                                getOptionLabel={(option) =>
                                    `${option.name} - ${option.role}`
                                }
                                onChange={(e, value) => {
                                    setData(
                                        "reportingAuthority",
                                        value ? value.id : null
                                    );
                                }}
                                renderInput={(props) => (
                                    <TextField
                                        {...props}
                                        label="Reporting Authority"
                                    />
                                )}
                            />
                        </Grid>
                        <Grid item xs={12}>
                            <Typography variant="h6">Role</Typography>
                            <RadioGroup
                                label="Role"
                                value={data.roleName}
                                onChange={(e) =>
                                    setData("roleName", e.target.value)
                                }
                                row
                            >
                                {roles.map((role, index) => (
                                    <FormControlLabel
                                        key={index}
                                        value={role.name}
                                        control={<Radio />}
                                        label={role.name}
                                    />
                                ))}
                            </RadioGroup>
                        </Grid>
                        <Grid item xs={12}>
                            <LoadingButton
                                type="submit"
                                variant="contained"
                                loading={processing}
                            >
                                Submit
                            </LoadingButton>
                        </Grid>
                        <Grid item xs={12}>
                            <Grid item xs={12} mt={3}>
                                <Typography variant="h5">
                                    Permissions
                                </Typography>
                            </Grid>
                            <Grid
                                item
                                container
                                xs={12}
                                columns={12}
                                spacing={1}
                            >
                                {groupPermissions.map((permission, index) => (
                                    <Grid
                                        key={index}
                                        item
                                        lg={4}
                                        md={6}
                                        xs={12}
                                    >
                                        <Paper sx={{ height: "100%" }}>
                                            <TableContainer
                                                sx={{ maxHeight: 440 }}
                                            >
                                                <Table
                                                    stickyHeader
                                                    size="small"
                                                >
                                                    <TableHead>
                                                        <TableRow>
                                                            <TableCell>
                                                                Name
                                                            </TableCell>
                                                            <TableCell align="right">
                                                                Action
                                                            </TableCell>
                                                        </TableRow>
                                                    </TableHead>
                                                    <TableBody>
                                                        {permission.permissions.map(
                                                            (p, i) => {
                                                                return (
                                                                    <TableRow
                                                                        key={i}
                                                                        sx={{
                                                                            "&:last-child td, &:last-child th":
                                                                                {
                                                                                    border: 0,
                                                                                },
                                                                        }}
                                                                    >
                                                                        <TableCell>
                                                                            {
                                                                                p.name
                                                                            }
                                                                        </TableCell>
                                                                        <TableCell align="right">
                                                                            <Switch
                                                                                size="small"
                                                                                color={
                                                                                    data.permissions.includes(
                                                                                        p.id
                                                                                    )
                                                                                        ? "secondary"
                                                                                        : "success"
                                                                                }
                                                                                checked={
                                                                                    data.permissions.includes(
                                                                                        p.id
                                                                                    ) ||
                                                                                    rolePermissions.includes(
                                                                                        p.id
                                                                                    )
                                                                                }
                                                                                disabled={rolePermissions.includes(
                                                                                    p.id
                                                                                )}
                                                                                onChange={() =>
                                                                                    handleSwitchChange(
                                                                                        p.id
                                                                                    )
                                                                                }
                                                                            />
                                                                        </TableCell>
                                                                    </TableRow>
                                                                );
                                                            }
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </TableContainer>
                                        </Paper>
                                    </Grid>
                                ))}
                            </Grid>
                        </Grid>
                    </Grid>
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default EditUser;
