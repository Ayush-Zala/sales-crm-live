import { types } from "@/Constant/constants";

import { yupResolver } from "@hookform/resolvers/yup";
import { router } from "@inertiajs/react";
import { Delete } from "@mui/icons-material";
import {
    Button,
    DialogActions,
    DialogContent,
    Grid,
    IconButton,
    InputAdornment,
    Stack,
    Typography,
} from "@mui/material";
import { Fragment } from "react";
import {
    SelectElement,
    TextFieldElement,
    useFieldArray,
    useForm,
} from "react-hook-form-mui";
import toast from "react-hot-toast";
import * as Yup from "yup";

const schema = Yup.object().shape({
    fname: Yup.string().required("First Name is required"),
    lname: Yup.string().required("Last Name is required"),
    designation: Yup.string().required("Designation is required"),
    linkdinurl: Yup.string().required("Linkdin is required"),
    company: Yup.string().required("Company is required"),
    clientPhone: Yup.array().of(
        Yup.object().shape({
            type: Yup.string().required("Phone Type is required"),
            phone: Yup.number().required("Phone is required"),
        })
    ),
    clientEmail: Yup.array().of(
        Yup.object().shape({
            type: Yup.string().required("Email Type is required"),
            email: Yup.string().email().required("Email is required"),
        })
    ),
});

export default function CreateClient({ companyId, handleClose }) {
    const url = router.page.url;
    const defaultValues = {
        fname: "",
        lname: "",
        designation: "",
        linkdinurl: "",
        clientPhone: [{ type: "", phone: "" }],
        clientEmail: [{ type: "", email: "" }],
        company: companyId || "",
    };

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
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("client.store"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(data),
        })
            .then((response) => response.json())
            .then((data) => {
                toast.success("Client created successfully");
                reset();
                handleClose();
                router.get(
                    url,
                    {},
                    { preserveScroll: true, preserveState: true }
                );
            })
            .catch((error) => {
                console.error(error);
                toast.error("Error creating client");
            });
    };

    return (
        <>
            <DialogContent dividers>
                <Grid item container xs={12} spacing={2}>
                    <Grid item xs={12}>
                        <ClientInformationComponent control={control} />
                    </Grid>

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
            </DialogContent>
            <DialogActions>
                <Button
                    type="submit"
                    variant="contained"
                    onClick={handleSubmit(onSubmit)}
                >
                    submit
                </Button>
                <Button
                    variant="outlined"
                    color="error"
                    sx={{ ml: 2 }}
                    onClick={handleClose}
                >
                    cancel
                </Button>
            </DialogActions>
        </>
    );
}

const ClientInformationComponent = ({ control }) => {
    return (
        <Grid item container columns={12} spacing={2}>
            <Grid item xs={12} md={6}>
                <TextFieldElement
                    control={control}
                    name="fname"
                    label="First Name"
                    required
                />
            </Grid>
            <Grid item xs={12} md={6}>
                <TextFieldElement
                    control={control}
                    name="lname"
                    label="Last Name"
                    required
                />
            </Grid>
            <Grid item xs={12} md={6}>
                <TextFieldElement
                    control={control}
                    name="linkdinurl"
                    label="Linkdin"
                    required
                />
            </Grid>
            <Grid item xs={12} md={6}>
                <TextFieldElement
                    control={control}
                    name="designation"
                    label="Designation"
                    required
                />
            </Grid>
        </Grid>
    );
};

const PhoneListComponent = ({
    clientPhoneFields,
    control,
    appendClientPhone,
    removeClientPhone,
}) => {
    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Phone</Typography>
            </Grid>
            <Grid item container xs={12} spacing={1}>
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
                                                {clientPhoneFields.length -
                                                    1 ===
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
                                                {index > 0 && (
                                                    <IconButton
                                                        size="small"
                                                        color="error"
                                                        onClick={() =>
                                                            removeClientPhone(
                                                                index
                                                            )
                                                        }
                                                        variant="contained"
                                                    >
                                                        <Delete fontSize="small" />
                                                    </IconButton>
                                                )}
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
    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Email</Typography>
            </Grid>
            <Grid item container xs={12} spacing={1}>
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
                                required
                                InputProps={{
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            <Stack direction="row">
                                                {clientEmailFields.length -
                                                    1 ===
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
                                                {index > 0 && (
                                                    <IconButton
                                                        size="small"
                                                        color="error"
                                                        onClick={() =>
                                                            removeClientEmail(
                                                                index
                                                            )
                                                        }
                                                        variant="contained"
                                                    >
                                                        <Delete fontSize="small" />
                                                    </IconButton>
                                                )}
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
