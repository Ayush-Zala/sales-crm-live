import { yupResolver } from "@hookform/resolvers/yup";
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
import {
    CheckboxElement,
    FormContainer,
    RadioButtonGroup,
    SelectElement,
    TextFieldElement,
    useForm,
} from "react-hook-form-mui";
import * as Yup from "yup";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";
import { LoadingButton } from "@mui/lab";
import { Fragment } from "react";
import toast from "react-hot-toast";

const defaultValues = {
    username: "",
    email: "",
    password: "",
    roleName: "Admin",
    permissions: [],
    reportingAuthority: "",
};

const schema = Yup.object().shape({
    username: Yup.string().required("username is required"),
    email: Yup.string().email().required("Email is required"),
    password: Yup.string().required("password is required"),
    roleName: Yup.string().required("Role is required"),
    permissions: Yup.array().required("Permissions is required"),
    reportingAuthority: Yup.string().required("User is required"),
});

const CreateUser = ({
    auth,
    roles,
    groupPermissions,
    reportingAuthorities,
}) => {
    const { control, handleSubmit, reset } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    const reportingAuthoritiesArray = reportingAuthorities.map((user) => ({
        label: `${user.name} - ${user.role}`,
        value: user.id,
    }));

    const onSubmit = async (data) => {
        const permissionList = data.permissions.reduce((acc, value, index) => {
            if (value) acc.push(index);
            return acc;
        }, []);

        const selectedPermissions = permissionList
            .map((index) => {
                const permission = groupPermissions.find(
                    (perm) => perm.permission_id === index
                );
                return permission ? permission.permission_name : null;
            })
            .filter(Boolean);

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("user.stores"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ ...data, permissions: selectedPermissions }),
        })
            .then((response) => response.json())
            .then((res) => {
                toast.success(res.message);
                reset(defaultValues);
            })
            .catch((error) => {
                toast.error("Something went wrong");
                console.error(error);
            });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Create User" />
            <MainContentTemplate
                title="Create user"
                subtitle="Create user by fillling details here"
                button="Go back"
                href={route("user")}
            >
                <Grid item xs={12}>
                    <FormContainer
                        defaultValues={defaultValues}
                        onSuccess={handleSubmit(onSubmit)}
                    >
                        <Grid item container xs={12} spacing={2}>
                            <Grid item xs={12}>
                                <TextFieldElement
                                    name="username"
                                    label="Username"
                                    control={control}
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <TextFieldElement
                                    name="email"
                                    label="Email"
                                    control={control}
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <TextFieldElement
                                    name="password"
                                    label="Password"
                                    control={control}
                                    type="password"
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <SelectElement
                                    name="reportingAuthority"
                                    label="Reporting Authorities"
                                    control={control}
                                    options={reportingAuthoritiesArray}
                                    labelKey="label"
                                    valueKey="value"
                                />
                            </Grid>
                            <RolesSection control={control} roles={roles} />
                            <PermissionsSection
                                control={control}
                                groupPermissions={groupPermissions}
                            />
                            <Grid item xs={12}>
                                <LoadingButton
                                    type="submit"
                                    variant="contained"
                                >
                                    Submit
                                </LoadingButton>
                            </Grid>
                        </Grid>
                    </FormContainer>
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default CreateUser;

const RolesSection = ({ control, roles }) => {
    return (
        <Grid item xs={12}>
            <RadioButtonGroup
                name="roleName"
                label="Role"
                control={control}
                options={roles}
                labelKey="name"
                valueKey="name"
                row
            />
        </Grid>
    );
};

const PermissionsSection = ({ control, groupPermissions }) => {
    const groupedData = groupPermissions.reduce((acc, item) => {
        // Find the group in the accumulator array
        let group = acc.find((g) => g.group_name === item.group_name);

        // If group doesn't exist, create it and add to the accumulator
        if (!group) {
            group = {
                group_name: item.group_name,
                permissions: [],
            };
            acc.push(group);
        }

        // Add the current item to the permissions array of the group
        group.permissions.push({
            permission_name: item.permission_name,
            permission_id: item.permission_id,
            group_id: item.group_id,
        });

        return acc;
    }, []);

    return (
        <Fragment>
            <Grid item xs={12} mt={3}>
                <Typography variant="h5">Permissions</Typography>
            </Grid>
            <Grid item container xs={12} columns={12} spacing={1}>
                {groupedData.map((group, index) => (
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
                            <Table aria-label="simple table" stickyHeader>
                                <TableHead>
                                    <TableRow>
                                        <TableCell>Name</TableCell>
                                        <TableCell>Allow</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {group.permissions.map((perm, index) => (
                                        <TableRow
                                            key={index}
                                            sx={{
                                                "&:last-child td, &:last-child th":
                                                    {
                                                        border: 0,
                                                    },
                                            }}
                                        >
                                            <TableCell
                                                component="th"
                                                scope="row"
                                            >
                                                {perm.permission_name}
                                            </TableCell>
                                            <TableCell
                                                component="th"
                                                scope="row"
                                            >
                                                <CheckboxElement
                                                    name={`permissions.${perm.permission_id}`}
                                                    control={control}
                                                    value={perm.permission_id}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    </Grid>
                ))}
            </Grid>
        </Fragment>
    );
};

// import { yupResolver } from "@hookform/resolvers/yup";
// import {
//     Grid,
//     Paper,
//     Table,
//     TableBody,
//     TableCell,
//     TableContainer,
//     TableHead,
//     TableRow,
//     Typography,
//     TextField,
// } from "@mui/material";

// import Radio from "@mui/material/Radio";
// import RadioGroup from "@mui/material/RadioGroup";
// import FormControlLabel from "@mui/material/FormControlLabel";
// import FormControl from "@mui/material/FormControl";
// import FormLabel from "@mui/material/FormLabel";

// // import {
// //     CheckboxElement,
// //     FormContainer,
// //     RadioButtonGroup,
// //     SelectElement,
// //     TextFieldElement,
// //     useForm,
// // } from "react-hook-form-mui";
// import * as Yup from "yup";

// import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
// import { MainContentTemplate } from "@/Layouts/components/main-content-template";
// import { Head, useForm } from "@inertiajs/react";
// import { LoadingButton } from "@mui/lab";
// import toast from "react-hot-toast";

// const defaultValues = {
//     username: "",
//     email: "",
//     password: "",
//     roleName: "Admin",
//     permissions: [],
//     reportingAuthority: "",
// };

// const schema = Yup.object().shape({
//     username: Yup.string().required("username is required"),
//     email: Yup.string().email().required("Email is required"),
//     password: Yup.string().required("password is required"),
//     roleName: Yup.string().required("Role is required"),
//     permissions: Yup.array().required("Permissions is required"),
//     reportingAuthority: Yup.string().required("User is required"),
// });

// const CreateUser = ({
//     auth,
//     roles,
//     groupPermissions,
//     reportingAuthorities,
// }) => {
//     const { data, setData, post, processing, errors } = useForm({
//         email: "",
//         password: "",
//         remember: false,
//     });

//     // const { control, handleSubmit, reset } = useForm({
//     //     defaultValues,
//     //     resolver: yupResolver(schema),
//     // });

//     const submit = (e) => {
//         e.preventDefault();

//         post(route("user.stores"));
//     };

//     const onSubmit = async (data) => {
//         const permissionList = data.permissions.reduce((acc, value, index) => {
//             if (value) acc.push(index);
//             return acc;
//         }, []);

//         const selectedPermissions = permissionList
//             .map((index) => {
//                 const permission = groupPermissions.find(
//                     (perm) => perm.permission_id === index
//                 );
//                 return permission ? permission.permission_name : null;
//             })
//             .filter(Boolean);

//         const csrfToken = document
//             .querySelector('meta[name="csrf-token"]')
//             .getAttribute("content");

//         fetch(route("user.stores"), {
//             method: "POST",
//             headers: {
//                 "Content-Type": "application/json",
//                 "X-CSRF-TOKEN": csrfToken,
//             },
//             body: JSON.stringify({ ...data, permissions: selectedPermissions }),
//         })
//             .then((response) => response.json())
//             .then((res) => {
//                 toast.success(res.message);
//                 reset(defaultValues);
//             })
//             .catch((error) => {
//                 toast.error("Something went wrong");
//                 console.error(error);
//             });
//     };

//     return (
//         <AuthenticatedLayout user={auth.user}>
//             <Head title="Create User" />
//             <MainContentTemplate
//                 title="Create user"
//                 subtitle="Create user by fillling details here"
//                 button="Go back"
//                 href={route("user")}
//             >
//                 <Grid item xs={12}>
//                     <Grid
//                         item
//                         container
//                         xs={12}
//                         spacing={2}
//                         component="form"
//                         onSubmit={submit}
//                     >
//                         <Grid item xs={12}>
//                             <TextField
//                                 name="username"
//                                 label="Username"
//                                 value={data.username}
//                                 onChange={(e) =>
//                                     setData("username", e.target.value)
//                                 }
//                             />
//                         </Grid>
//                         <Grid item xs={12}>
//                             <TextField
//                                 name="email"
//                                 label="Email"
//                                 value={data.email}
//                                 onChange={(e) =>
//                                     setData("email", e.target.value)
//                                 }
//                             />
//                         </Grid>
//                         <Grid item xs={12}>
//                             <TextField
//                                 name="password"
//                                 label="Password"
//                                 type="password"
//                                 value={data.password}
//                                 onChange={(e) =>
//                                     setData("password", e.target.value)
//                                 }
//                             />
//                         </Grid>
//                         <Grid item xs={12}>
//                             <TextField
//                                 select
//                                 name="reportingAuthority"
//                                 label="Reporting Authorities"
//                                 value={data.reportingAuthority}
//                                 SelectProps={{
//                                     native: true,
//                                 }}
//                             >
//                                 {reportingAuthorities.map((item, index) => (
//                                     <option key={index} value={item.id}>
//                                         {item.name}
//                                     </option>
//                                 ))}
//                             </TextField>
//                         </Grid>
//                         <Grid item xs={12}>
//                             <FormControl>
//                                 <FormLabel id="demo-radio-buttons-group-label">
//                                     Role
//                                 </FormLabel>
//                                 <RadioGroup
//                                     row
//                                     aria-labelledby="demo-radio-buttons-group-label"
//                                     defaultValue="Admin"
//                                     name="radio-buttons-group"
//                                     value={data.roleName}
//                                     onChange={(e) =>
//                                         setData("roleName", e.target.value)
//                                     }
//                                 >
//                                     {roles.map((role, index) => (
//                                         <FormControlLabel
//                                             key={index}
//                                             value={role.id}
//                                             control={<Radio />}
//                                             label={role.name}
//                                         />
//                                     ))}
//                                 </RadioGroup>
//                             </FormControl>
//                         </Grid>

//                         {/* <RolesSection control={control} roles={roles} /> */}
//                         {/* <PermissionsSection
//                                 control={control}
//                                 groupPermissions={groupPermissions}
//                             /> */}
//                         <Grid item xs={12}>
//                             <LoadingButton type="submit" variant="contained">
//                                 Submit
//                             </LoadingButton>
//                         </Grid>
//                     </Grid>
//                 </Grid>
//             </MainContentTemplate>
//         </AuthenticatedLayout>
//     );
// };

// export default CreateUser;

// // const PermissionsSection = ({ control, groupPermissions }) => {
// //     const groupedData = groupPermissions.reduce((acc, item) => {
// //         // Find the group in the accumulator array
// //         let group = acc.find((g) => g.group_name === item.group_name);

// //         // If group doesn't exist, create it and add to the accumulator
// //         if (!group) {
// //             group = {
// //                 group_name: item.group_name,
// //                 permissions: [],
// //             };
// //             acc.push(group);
// //         }

// //         // Add the current item to the permissions array of the group
// //         group.permissions.push({
// //             permission_name: item.permission_name,
// //             permission_id: item.permission_id,
// //             group_id: item.group_id,
// //         });

// //         return acc;
// //     }, []);

// //     return (
// //         <Fragment>
// //             <Grid item xs={12} mt={3}>
// //                 <Typography variant="h5">Permissions</Typography>
// //             </Grid>
// //             <Grid item container xs={12} columns={12} spacing={1}>
// //                 {groupedData.map((group, index) => (
// //                     <Grid item xs={4} key={index}>
// //                         <Typography
// //                             variant="h6"
// //                             fontSize={20}
// //                             sx={{ textAlign: "center" }}
// //                         >
// //                             {group.group_name}
// //                         </Typography>
// //                         <TableContainer
// //                             component={Paper}
// //                             sx={{ maxHeight: 500 }}
// //                         >
// //                             <Table aria-label="simple table" stickyHeader>
// //                                 <TableHead>
// //                                     <TableRow>
// //                                         <TableCell>Name</TableCell>
// //                                         <TableCell>Allow</TableCell>
// //                                     </TableRow>
// //                                 </TableHead>
// //                                 <TableBody>
// //                                     {group.permissions.map((perm, index) => (
// //                                         <TableRow
// //                                             key={index}
// //                                             sx={{
// //                                                 "&:last-child td, &:last-child th":
// //                                                     {
// //                                                         border: 0,
// //                                                     },
// //                                             }}
// //                                         >
// //                                             <TableCell
// //                                                 component="th"
// //                                                 scope="row"
// //                                             >
// //                                                 {perm.permission_name}
// //                                             </TableCell>
// //                                             <TableCell
// //                                                 component="th"
// //                                                 scope="row"
// //                                             >
// //                                                 <CheckboxElement
// //                                                     name={`permissions.${perm.permission_id}`}
// //                                                     control={control}
// //                                                     value={perm.permission_id}
// //                                                 />
// //                                             </TableCell>
// //                                         </TableRow>
// //                                     ))}
// //                                 </TableBody>
// //                             </Table>
// //                         </TableContainer>
// //                     </Grid>
// //                 ))}
// //             </Grid>
// //         </Fragment>
// //     );
// // };
