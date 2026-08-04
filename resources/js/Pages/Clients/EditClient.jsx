import { yupResolver } from "@hookform/resolvers/yup";
import { Head } from "@inertiajs/react";
import { Delete } from "@mui/icons-material";
import {
    Button,
    Grid,
    IconButton,
    InputAdornment,
    Stack,
    Typography,
} from "@mui/material";
import { Fragment } from "react";
import {
    FormContainer,
    SelectElement,
    TextFieldElement,
    useFieldArray,
    useForm,
} from "react-hook-form-mui";
import toast from "react-hot-toast";
import * as Yup from "yup";

import { types } from "@/Constant/constants";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";

export default function EditClient({ auth, client, clientPhone, clientEmail }) {
    const defaultValues = {
        fname: client.fname,
        lname: client.lname,
        designation: client.designation,
        linkdinurl: client.linkdinurl,
        clientPhone:
            clientPhone.length > 0
                ? clientPhone
                : [{ phoneId: "", clientId: "", type: "", phone: "" }],
        clientEmail:
            clientEmail.length > 0
                ? clientEmail
                : [{ emailId: "", clientId: "", type: "", email: "" }],
    };

    const schema = Yup.object().shape({
        fname: Yup.string().required("First Name is required"),
        lname: Yup.string().required("Last Name is required"),
        designation: Yup.string().required("Designation is required"),
        linkdinurl: Yup.string(),
        clientPhone: Yup.array().of(
            Yup.object().shape({
                type: Yup.string().required("Phone Type is required"),
                phone: Yup.number().required("Phone is required"),
            })
        ),
        clientEmail: Yup.array().of(
            Yup.object().shape({
                type: Yup.string(),
                email: Yup.string().email(),
            })
        ),
    });

    const { control, handleSubmit, reset } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    const {
        fields: clientPhoneFields,
        append: appendClientPhone,
        remove: removeClientPhone,
    } = useFieldArray({
        control,
        name: "clientPhone",
    });

    const {
        fields: clientEmailFields,
        append: appendClientEmail,
        remove: removeClientEmail,
    } = useFieldArray({
        control,
        name: "clientEmail",
    });

    const onSubmit = (data) => {
        const formData = {
            ...data,
            id: client.id,
        };

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("client.update"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(formData),
        })
            .then((res) => res.json())
            .then((data) => {
                toast.success(data.message);
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Edit Client" />
            <MainContentTemplate
                title="Edit Client"
                subtitle="Edit client from here"
                button="Go back"
                onClick={() => window.history.back()}
            >
                <Grid item xs={12}>
                    <FormContainer
                        onSuccess={handleSubmit(onSubmit)}
                        defaultValues={defaultValues}
                    >
                        <Grid container spacing={2}>
                            <Grid item xs={3}>
                                <TextFieldElement
                                    control={control}
                                    name="fname"
                                    label="First Name"
                                    className="w-full"
                                    required
                                />
                            </Grid>
                            <Grid item xs={3}>
                                <TextFieldElement
                                    control={control}
                                    name="lname"
                                    label="Last Name"
                                    className="w-full"
                                    required
                                />
                            </Grid>
                            <Grid item xs={3}>
                                <TextFieldElement
                                    control={control}
                                    name="linkdinurl"
                                    label="Linkdin"
                                    className="w-full"
                                />
                            </Grid>
                            <Grid item xs={3}>
                                <TextFieldElement
                                    control={control}
                                    name="designation"
                                    label="Designation"
                                    className="w-full"
                                    required
                                />
                            </Grid>
                        </Grid>
                        <Grid item container xs={12} spacing={2} mt={1}>
                            <Grid item xs={12}>
                                <PhoneListComponent
                                    clientPhoneFields={clientPhoneFields}
                                    control={control}
                                    appendClientPhone={appendClientPhone}
                                    removeClientPhone={removeClientPhone}
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <EmailListComponent
                                    clientEmailFields={clientEmailFields}
                                    control={control}
                                    appendClientEmail={appendClientEmail}
                                    removeClientEmail={removeClientEmail}
                                />
                            </Grid>
                        </Grid>
                        <Button
                            type="submit"
                            variant="contained"
                            sx={{ mt: 5 }}
                        >
                            submit
                        </Button>
                    </FormContainer>
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}

const PhoneListComponent = ({
    clientPhoneFields,
    control,
    appendClientPhone,
    removeClientPhone,
}) => {
    const handleDeletePhone = (index) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("client.deletephone"), {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                phoneId: clientPhoneFields[index].phoneId,
                clientId: clientPhoneFields[index].clientId,
            }),
        })
            .then(async (response) => {
                const res = await response.json();
                if (response.ok) {
                    toast.success(res.message);
                    removeClientPhone(index);
                } else {
                    toast.error(res.error);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                toast.error("Error deleting phone");
            });
    };

    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Phone</Typography>
            </Grid>
            <Grid item container xs={12} md={6} spacing={1}>
                {clientPhoneFields.map((field, index) => (
                    <Grid
                        key={field.id}
                        item
                        container
                        columns={12}
                        spacing={1.5}
                    >
                        <Grid item xs={12} md={4}>
                            <SelectElement
                                control={control}
                                label="Phone Type"
                                name={`clientPhone[${index}].type`}
                                options={types}
                                required
                            />
                        </Grid>
                        <Grid item xs={12} md={8}>
                            <TextFieldElement
                                control={control}
                                name={`clientPhone[${index}].phone`}
                                label="Phone"
                                required
                                InputProps={{
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            <Stack direction="row">
                                                {clientPhoneFields.length - 1 ==
                                                    index && (
                                                    <Button
                                                        color="success"
                                                        size="small"
                                                        onClick={() =>
                                                            appendClientPhone(
                                                                "clientPhone"
                                                            )
                                                        }
                                                    >
                                                        add
                                                    </Button>
                                                )}

                                                <IconButton
                                                    size="small"
                                                    color="error"
                                                    onClick={() =>
                                                        handleDeletePhone(index)
                                                    }
                                                    variant="contained"
                                                >
                                                    <Delete fontSize="small" />
                                                </IconButton>
                                            </Stack>
                                        </InputAdornment>
                                    ),
                                }}
                            />
                        </Grid>
                    </Grid>
                ))}
            </Grid>
        </Fragment>
    );
};

const EmailListComponent = ({
    clientEmailFields,
    control,
    appendClientEmail,
    removeClientEmail,
}) => {
    const handleDeleteEmail = (index) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("client.deleteemail"), {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                emailId: clientEmailFields[index].emailId,
                clientId: clientEmailFields[index].clientId,
            }),
        })
            .then(async (response) => {
                const res = await response.json();
                if (response.ok) {
                    toast.success(res.message);
                    removeClientEmail(index);
                } else {
                    toast.error(res.error);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                toast.error("Error deleting email");
            });
    };

    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Email</Typography>
            </Grid>
            <Grid item container xs={12} md={6} spacing={1}>
                {clientEmailFields.map((field, index) => (
                    <Grid
                        item
                        container
                        key={field.id}
                        columns={12}
                        spacing={1.5}
                    >
                        <Grid item xs={12} md={4}>
                            <SelectElement
                                control={control}
                                label="Email Type"
                                name={`clientEmail[${index}].type`}
                                options={types}
                            />
                        </Grid>
                        <Grid item xs={12} md={8}>
                            <TextFieldElement
                                type="email"
                                control={control}
                                name={`clientEmail[${index}].email`}
                                label="Email"
                                InputProps={{
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            <Stack direction="row">
                                                {clientEmailFields.length - 1 ==
                                                    index && (
                                                    <Button
                                                        color="success"
                                                        size="small"
                                                        onClick={() =>
                                                            appendClientEmail(
                                                                "clientEmail"
                                                            )
                                                        }
                                                    >
                                                        add
                                                    </Button>
                                                )}

                                                <IconButton
                                                    size="small"
                                                    color="error"
                                                    onClick={() =>
                                                        handleDeleteEmail(index)
                                                    }
                                                    variant="contained"
                                                >
                                                    <Delete fontSize="small" />
                                                </IconButton>
                                            </Stack>
                                        </InputAdornment>
                                    ),
                                }}
                            />
                        </Grid>
                    </Grid>
                ))}
            </Grid>
        </Fragment>
    );
};
