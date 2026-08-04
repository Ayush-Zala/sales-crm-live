import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { yupResolver } from "@hookform/resolvers/yup";
import { Head } from "@inertiajs/react";
import {
    Grid,
    Paper,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
} from "@mui/material";
import { Fragment, useEffect, useState } from "react";
import {
    CheckboxElement,
    FormContainer,
    TextFieldElement,
    useForm,
} from "react-hook-form-mui";
import * as Yup from "yup";

const ViewRole = ({ auth, role, permissions }) => {
    const [groupedData, setGroupedData] = useState(null);

    const defaultValues = {
        role_name: role?.name || "",
        role_code: role?.code || "",
        permissions:
            role?.permissions?.reduce((acc, permission) => {
                acc[permission.permission_id] = permission.assigned || false; // Default to false if not set
                return acc;
            }, {}) || {},
    };

    const schema = Yup.object().shape({
        role_name: Yup.string().required("Role Name is required"),
        role_code: Yup.string().required("Role Code is required"),
    });

    const { control } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    // Function to group permissions by `group_name`
    const groupByGroupName = (permissions) => {
        const grouped = permissions.reduce((groups, permission) => {
            if (!permission.group_name) {
                console.warn("Permission group_name is undefined:", permission);
                return groups; // Skip this permission if group_name is undefined
            }

            if (!groups[permission.group_name]) {
                groups[permission.group_name] = {
                    group_name: permission.group_name,
                    permissions: [],
                };
            }

            groups[permission.group_name].permissions.push(permission);
            return groups;
        }, {});

        setGroupedData(Object.values(grouped));
    };

    useEffect(() => {
        groupByGroupName(permissions);
    }, [permissions]);

    return (
        <Fragment>
            <AuthenticatedLayout user={auth.user}>
                <Head title="Views Roles" />
                <MainContentTemplate
                    title="Views Roles"
                    subtitle="View roles here"
                    button="Go back"
                    href={route("role.index")}
                >
                    <Grid item xs={12}>
                        <FormContainer defaultValues={defaultValues}>
                            <Grid container spacing={2} columns={12}>
                                <Grid item xs={6}>
                                    <TextFieldElement
                                        control={control}
                                        name="role_name"
                                        label="Role Name"
                                        type="text"
                                        disabled
                                    />
                                </Grid>

                                <Grid item xs={6}>
                                    <TextFieldElement
                                        control={control}
                                        name="role_code"
                                        label="Role Code"
                                        type="text"
                                        disabled
                                    />
                                </Grid>

                                {groupedData &&
                                    groupedData.map((group, index) => (
                                        <Grid item xs={4} key={index}>
                                            <Typography
                                                variant="h6"
                                                fontSize={20}
                                                sx={{ textAlign: "center" }}
                                            >
                                                {group.group_name}
                                            </Typography>
                                            <TableContainer
                                                component={Paper}
                                                sx={{ maxHeight: 500 }}
                                            >
                                                <Table
                                                    aria-label="permissions table"
                                                    stickyHeader
                                                >
                                                    <TableHead>
                                                        <TableRow>
                                                            <TableCell>
                                                                Permission Name
                                                            </TableCell>
                                                            <TableCell>
                                                                Allow
                                                            </TableCell>
                                                        </TableRow>
                                                    </TableHead>
                                                    <TableBody>
                                                        {group.permissions.map(
                                                            (perm, index) => (
                                                                <TableRow
                                                                    key={index}
                                                                >
                                                                    <TableCell>
                                                                        {
                                                                            perm.permission_name
                                                                        }
                                                                    </TableCell>
                                                                    <TableCell>
                                                                        <CheckboxElement
                                                                            name={`permissions.${perm.permission_id}`}
                                                                            control={
                                                                                control
                                                                            }
                                                                            checked={
                                                                                perm.assigned
                                                                            }
                                                                            disabled
                                                                        />
                                                                    </TableCell>
                                                                </TableRow>
                                                            )
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </TableContainer>
                                        </Grid>
                                    ))}
                            </Grid>
                        </FormContainer>
                    </Grid>
                </MainContentTemplate>
            </AuthenticatedLayout>
        </Fragment>
    );
};

export default ViewRole;
