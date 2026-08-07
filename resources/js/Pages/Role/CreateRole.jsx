import { Head, useForm } from "@inertiajs/react";
import LoadingButton from "@mui/lab/LoadingButton";
import Grid from "@mui/material/Grid";
import Paper from "@mui/material/Paper";
import Switch from "@mui/material/Switch";
import Table from "@mui/material/Table";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TableContainer from "@mui/material/TableContainer";
import TableHead from "@mui/material/TableHead";
import TableRow from "@mui/material/TableRow";
import TextField from "@mui/material/TextField";
import { Fragment } from "react";
import toast from "react-hot-toast";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";

const CreateRole = ({ auth, permissions }) => {
    const { data, setData, post, processing, errors, wasSuccessful, reset } = useForm({
        role_name: "",
        currentPermissions: [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        post(route("role.store"), {
            onSuccess: (res) => {
                toast.success(res.props.flash.success);
                reset();
            },
            onError: (error) => {
                console.log(error);
                toast.error("An error occurred. Please try again.");
            },
        });
    };

    const handleSwitchChange = (id) => {
        const index = data.currentPermissions.indexOf(id);

        if (index === -1) {
            setData("currentPermissions", [...data.currentPermissions, id]);
        } else {
            const newPermissions = data.currentPermissions.filter(
                (permission) => permission !== id
            );

            setData("currentPermissions", newPermissions);
        }
    };

    return (
        <Fragment>
            <AuthenticatedLayout user={auth.user}>
                <Head title="Create Role" />
                <MainContentTemplate
                    title="Create Role"
                    subtitle="Create a new role"
                    button="Go back"
                    href={route("role.index")}
                >
                    <Grid
                        container
                        component="form"
                        item
                        xs={12}
                        spacing={2}
                        onSubmit={handleSubmit}
                    >
                        <Grid
                            container
                            item
                            xs={12}
                            spacing={2}
                            alignItems="center"
                        >
                            <Grid item xs={10}>
                                <TextField
                                    label="Role name"
                                    value={data.role_name}
                                    error={errors.role_name}
                                    onChange={(e) => {
                                        setData("role_name", e.target.value);
                                    }}
                                />
                            </Grid>
                            <Grid item xs={2}>
                                <LoadingButton
                                    fullWidth
                                    loading={processing}
                                    type="submit"
                                    variant="contained"
                                >
                                    Create Role
                                </LoadingButton>
                            </Grid>
                        </Grid>
                        {permissions.map((permission, index) => (
                            <Grid key={index} item lg={4} md={6} xs={12}>
                                <Paper sx={{ height: "100%" }}>
                                    <TableContainer sx={{ maxHeight: 440 }}>
                                        <Table stickyHeader size="small">
                                            <TableHead>
                                                <TableRow>
                                                    <TableCell>Name</TableCell>
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
                                                                    {p.name}
                                                                </TableCell>
                                                                <TableCell align="right">
                                                                    <Switch
                                                                        size="small"
                                                                        color="success"
                                                                        checked={data.currentPermissions.includes(
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
                </MainContentTemplate>
            </AuthenticatedLayout>
        </Fragment>
    );
};

export default CreateRole;
