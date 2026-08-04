import { yupResolver } from "@hookform/resolvers/yup";
import { Head } from "@inertiajs/react";
import { Delete } from "@mui/icons-material";
import LoadingButton from "@mui/lab/LoadingButton";
import {
    Box,
    Button,
    Grid,
    IconButton,
    InputAdornment,
    Stack,
    Typography,
} from "@mui/material";
import { Fragment } from "react";
import {
    AutocompleteElement,
    RadioButtonGroup,
    SelectElement,
    TextFieldElement,
    useFieldArray,
    useForm,
} from "react-hook-form-mui";
import * as Yup from "yup";

import { timeZone, types, vendorTypes } from "@/Constant/constants";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { RHFPhoneInput } from "@/Components/rhf-phone-input";
import { useState } from "react";
import { useEffect } from "react";
import toast from "react-hot-toast";

const phone = { type: "primary", phone: "" };

const email = { type: "primary", email: "" };

const client = {
    fname: "",
    lname: "",
    designation: "",
    linkdinurl: "",
    clientPhone: [phone],
    clientEmail: [email],
};

const defaultValues = {
    vendorType: "fixed",
    companyName: "",
    faxNo: "",
    website: "",
    industry: { id: "", name: "" },
    source: "",
    client: [client],
    companyPhone: [phone],
    companyEmail: [email],
    houseNo: "",
    addressline1: "",
    addressline2: "",
    country: null,
    state: null,
    city: null,
    zipcode: "",
    timezone: "",
};

const schema = Yup.object().shape({
    vendorType: Yup.string().required("Vendor Type is required"),
    companyName: Yup.string().required("Company Name is required"),
    faxNo: Yup.string(),
    website: Yup.string(),
    industry: Yup.object().shape({
        id: Yup.number(),
        name: Yup.string(),
    }),
    source: Yup.string(),
    client: Yup.array().of(
        Yup.object().shape({
            fname: Yup.string(),
            lname: Yup.string(),
            designation: Yup.string(),
            linkdinurl: Yup.string(),
            clientPhone: Yup.array().of(
                Yup.object().shape({
                    type: Yup.string().required(),
                    phone: Yup.string()
                        .typeError("Phone is required")
                        .required(),
                })
            ),
            clientEmail: Yup.array().of(
                Yup.object().shape({
                    type: Yup.string().required(),
                    email: Yup.string()
                        .email()
                        .typeError("Email is required")
                        .required(),
                })
            ),
        })
    ),
    companyPhone: Yup.array().of(
        Yup.object().shape({
            type: Yup.string().required(),
            phone: Yup.string().typeError("Phone is required").required(),
        })
    ),
    companyEmail: Yup.array().of(
        Yup.object().shape({
            type: Yup.string().required(),
            email: Yup.string()
                .email()
                .typeError("Email is required")
                .required(),
        })
    ),
    houseNo: Yup.string(),
    addressline1: Yup.string(),
    addressline2: Yup.string(),
    country: Yup.object()
        .shape({
            id: Yup.number(),
            label: Yup.string(),
        })
        .required("Country is required"),
    state: Yup.object()
        .shape({
            id: Yup.number(),
            label: Yup.string(),
        })
        .nullable(),
    city: Yup.object()
        .shape({
            id: Yup.number(),
            label: Yup.string(),
        })
        .nullable(),
    zipcode: Yup.string(),
    timezone: Yup.string(),
});

const CreateAccount = ({ auth, countries, industries }) => {
    const {
        control,
        watch,
        setValue,
        handleSubmit,
        reset,
        formState: { isSubmitting, isLoading, isDirty, isValid },
    } = useForm({
        mode: "all",
        defaultValues,
        resolver: yupResolver(schema),
    });

    const [states, setStates] = useState([]);
    const [cities, setCities] = useState([]);

    useEffect(() => {
        const country = watch("country");

        if (country) {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");

            fetch(route("address.getstates"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ country_id: country }),
            })
                .then((response) => response.json())
                .then((data) => {
                    const states = data.map((state) => ({
                        ...state,
                        value: state.id,
                        label: state.name,
                    }));
                    setStates(states);
                });
        }
    }, [watch("country")]);

    useEffect(() => {
        const state = watch("state");
        if (state) {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");

            fetch(route("address.getcities"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ state_id: state }),
            })
                .then((response) => response.json())
                .then((data) => {
                    const cities = data.map((city) => ({
                        ...city,
                        value: city.id,
                        label: city.name,
                    }));
                    setCities(cities);
                });
        }
    }, [watch("state")]);

    const submit = (data) => {
        const payload = {
            ...data,
            industry: data.industry.name,
            client: data.client.map((client) => ({
                ...client,
                clientPhone: client.clientPhone.map((phone) => ({
                    type: phone.phone ? phone.type : "",
                    phone: phone.phone,
                })),
                clientEmail: client.clientEmail.map((email) => ({
                    type: email.email ? email.type : "",
                    email: email.email,
                })),
            })),
            companyPhone: data.companyPhone.map((phone) => ({
                type: phone.phone ? phone.type : "",
                phone: phone.phone,
            })),
            country: data.country ? data.country.id : null,
            state: data.state ? data.state.id : null,
            city: data.city ? data.city.id : null,
        };

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("account.store"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(payload),
        })
            .then((response) => response.json())
            .then((data) => {
                toast.success(data.message);
                reset(defaultValues);
            })
            .catch((error) => {
                console.log(error);
                toast.error("Something went wrong");
            });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Create account" />
            <MainContentTemplate
                title="Create Account"
                subtitle="Create account from here"
                button="Go back"
                href={route("account.index")}
            >
                <Grid
                    item
                    xs={12}
                    component="form"
                    autoComplete="off"
                    noValidate
                    onSubmit={handleSubmit(submit)}
                >
                    <Grid container item spacing={2} xs={12}>
                        <Grid item xs={12}>
                            <RadioButtonGroup
                                row
                                required
                                control={control}
                                label="Vendor Type"
                                name="vendorType"
                                options={vendorTypes}
                            />
                        </Grid>
                        <Grid item lg={2.4} md={6} xs={12}>
                            <TextFieldElement
                                required
                                control={control}
                                name="companyName"
                                label="Company Name"
                            />
                        </Grid>
                        <Grid item lg={2.4} md={6} xs={12}>
                            <TextFieldElement
                                control={control}
                                name="website"
                                label="Website"
                            />
                        </Grid>
                        <Grid item lg={2.4} md={6} xs={12}>
                            <TextFieldElement
                                control={control}
                                name="faxNo"
                                label="Fax No"
                            />
                        </Grid>
                        <Grid item lg={2.4} md={6} xs={12}>
                            <AutocompleteElement
                                control={control}
                                name="industry"
                                label="Industry"
                                options={industries}
                                autocompleteProps={{
                                    getOptionLabel: (option) => option.name,
                                    isOptionEqualToValue: (option, value) =>
                                        option.name === value,
                                    renderOption: (props, option, state) => (
                                        <Box
                                            component="li"
                                            sx={{ typography: "body2", p: 1 }}
                                            {...props}
                                            key={option.id}
                                        >
                                            {option.name}
                                        </Box>
                                    ),
                                }}
                            />
                        </Grid>
                        <Grid item lg={2.4} md={12} xs={12}>
                            <TextFieldElement
                                control={control}
                                name="source"
                                label="Source"
                            />
                        </Grid>
                        <Grid item lg={6} xs={12}>
                            <CompanyPhones
                                setValue={setValue}
                                control={control}
                                countries={countries}
                            />
                        </Grid>
                        <Grid item lg={6} xs={12}>
                            <CompanyEmails control={control} />
                        </Grid>
                        <Grid item lg={4} xs={12}>
                            <TextFieldElement
                                control={control}
                                name="houseNo"
                                label="House No"
                            />
                        </Grid>
                        <Grid item lg={4} xs={12}>
                            <TextFieldElement
                                control={control}
                                name="addressline1"
                                label="Address Line 1"
                            />
                        </Grid>
                        <Grid item lg={4} xs={12}>
                            <TextFieldElement
                                control={control}
                                name="addressline2"
                                label="Address Line 2"
                            />
                        </Grid>
                        <Grid item lg={2.4} md={6} xs={12}>
                            <AutocompleteElement
                                control={control}
                                name="country"
                                label="Country"
                                required
                                options={countries}
                                autocompleteProps={{
                                    getOptionLabel: (option) => option.label,
                                    isOptionEqualToValue: (option, value) =>
                                        option.id === value.id,
                                    renderOption: (props, option, state) => (
                                        <Box
                                            component="li"
                                            sx={{ typography: "body2", p: 1 }}
                                            {...props}
                                            key={option.id}
                                        >
                                            {option.label}
                                        </Box>
                                    ),
                                    onChange: (e, value) => {
                                        setValue("state", null);
                                        setValue("city", null);
                                    },
                                }}
                            />
                        </Grid>
                        <Grid item lg={2.4} md={6} xs={12}>
                            <AutocompleteElement
                                control={control}
                                name="state"
                                label="State"
                                options={states}
                                autocompleteProps={{
                                    getOptionLabel: (option) => option.name,
                                    isOptionEqualToValue: (option, value) =>
                                        option.id === value.id,
                                    renderOption: (props, option, state) => (
                                        <Box
                                            component="li"
                                            sx={{ typography: "body2", p: 1 }}
                                            {...props}
                                            key={option.id}
                                        >
                                            {option.name}
                                        </Box>
                                    ),
                                    onChange: (e, value) => {
                                        setValue("city", null);
                                    },
                                }}
                            />
                        </Grid>
                        <Grid item lg={2.4} md={6} xs={12}>
                            <AutocompleteElement
                                control={control}
                                name="city"
                                label="City"
                                options={cities}
                                autocompleteProps={{
                                    getOptionLabel: (option) => option.name,
                                    isOptionEqualToValue: (option, value) =>
                                        option.id === value.id,
                                    renderOption: (props, option, state) => (
                                        <Box
                                            component="li"
                                            sx={{ typography: "body2", p: 1 }}
                                            {...props}
                                            key={option.id}
                                        >
                                            {option.name}
                                        </Box>
                                    ),
                                }}
                            />
                        </Grid>
                        <Grid item lg={2.4} md={6} xs={12}>
                            <TextFieldElement
                                control={control}
                                name="zipcode"
                                label="Zip Code"
                            />
                        </Grid>
                        <Grid item lg={2.4} md={12} xs={12}>
                            <SelectElement
                                control={control}
                                label="Time Zone"
                                name="timezone"
                                options={timeZone}
                            />
                        </Grid>
                        <Grid item xs={12}>
                            <Typography variant="h6">Client Details</Typography>
                        </Grid>
                        <Grid item xs={12}>
                            <ClientDetails
                                control={control}
                                setValue={setValue}
                                countries={countries}
                            />
                        </Grid>
                        <Grid item xs={12}>
                            <LoadingButton
                                loading={isSubmitting}
                                type="submit"
                                variant="contained"
                                disabled={!isDirty || !isValid}
                            >
                                Create Account
                            </LoadingButton>
                        </Grid>
                    </Grid>
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default CreateAccount;

const CompanyPhones = ({ setValue, control, countries }) => {
    const { fields, append, remove } = useFieldArray({
        control,
        name: "companyPhone",
    });

    return fields.map((item, index) => (
        <Grid
            key={item.id}
            container
            item
            spacing={2}
            sx={{
                mb: fields.length - 1 === index ? 0 : 2,
            }}
        >
            <Grid item lg={4} md={4} xs={12}>
                <SelectElement
                    control={control}
                    label="Phone Type"
                    name={`companyPhone[${index}].type`}
                    options={types}
                    required
                />
            </Grid>
            <Grid item lg={8} md={8} xs={12}>
                <RHFPhoneInput
                    control={control}
                    setValue={setValue}
                    countries={countries}
                    name={`companyPhone[${index}].phone`}
                    label="Phone number"
                    required
                    endAdornment={
                        <InputAdornment position="end" sx={{ mr: 1 }}>
                            {fields.length - 1 === index && (
                                <Button
                                    size="small"
                                    color="success"
                                    onClick={() => append(phone)}
                                >
                                    add
                                </Button>
                            )}
                            {index > 0 && (
                                <IconButton
                                    size="small"
                                    color="error"
                                    onClick={() => remove(index)}
                                >
                                    <Delete fontSize="small" />
                                </IconButton>
                            )}
                        </InputAdornment>
                    }
                />
            </Grid>
        </Grid>
    ));
};

const CompanyEmails = ({ control }) => {
    const { fields, append, remove } = useFieldArray({
        control,
        name: "companyEmail",
    });

    return fields.map((item, index) => (
        <Grid
            key={item.id}
            container
            item
            spacing={2}
            sx={{
                mb: fields.length - 1 === index ? 0 : 2,
            }}
        >
            <Grid item lg={4} md={4} xs={12}>
                <SelectElement
                    control={control}
                    label="Email Type"
                    name={`companyEmail[${index}].type`}
                    options={types}
                    required
                />
            </Grid>
            <Grid item lg={8} md={8} xs={12}>
                <TextFieldElement
                    control={control}
                    name={`companyEmail[${index}].email`}
                    label="Email Address"
                    required
                    InputProps={{
                        endAdornment: (
                            <InputAdornment position="end">
                                <Stack direction="row" spacing={0.2}>
                                    {fields.length - 1 === index && (
                                        <Button
                                            size="small"
                                            color="success"
                                            onClick={() => append(email)}
                                        >
                                            add
                                        </Button>
                                    )}
                                    {index > 0 && (
                                        <IconButton
                                            size="small"
                                            color="error"
                                            onClick={() => remove(index)}
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
    ));
};

const ClientDetails = ({ setValue, control, countries }) => {
    const { fields, append, remove } = useFieldArray({
        control,
        name: "client",
    });

    return fields.map((item, index) => (
        <Fragment key={item.id}>
            <Grid container item spacing={2}>
                <Grid item lg={3} md={4} xs={12}>
                    <TextFieldElement
                        control={control}
                        name={`client[${index}].fname`}
                        label="First Name"
                    />
                </Grid>
                <Grid item lg={3} md={4} xs={12}>
                    <TextFieldElement
                        control={control}
                        name={`client[${index}].lname`}
                        label="Last Name"
                    />
                </Grid>
                <Grid item lg={3} md={4} xs={12}>
                    <TextFieldElement
                        control={control}
                        name={`client[${index}].designation`}
                        label="Designation"
                    />
                </Grid>
                <Grid item lg={3} md={4} xs={12}>
                    <TextFieldElement
                        control={control}
                        name={`client[${index}].linkdinurl`}
                        label="Linkdin URL"
                    />
                </Grid>
                <Grid item lg={6} xs={12}>
                    <ClientPhones
                        control={control}
                        setValue={setValue}
                        name={`client[${index}].clientPhone`}
                        countries={countries}
                    />
                </Grid>
                <Grid item lg={6} xs={12}>
                    <ClientEmails
                        control={control}
                        name={`client[${index}].clientEmail`}
                    />
                </Grid>
                {/* append and remove */}
                <Grid item xs={12}>
                    <Button
                        size="small"
                        color="success"
                        onClick={() => append(client)}
                    >
                        Add Client
                    </Button>
                    {index > 0 && (
                        <Button
                            size="small"
                            color="error"
                            onClick={() => remove(index)}
                        >
                            Remove Client
                        </Button>
                    )}
                </Grid>
            </Grid>
        </Fragment>
    ));
};

const ClientPhones = ({ setValue, control, name, countries }) => {
    const { fields, append, remove } = useFieldArray({
        control,
        name,
    });

    return fields.map((item, index) => (
        <Grid
            key={item.id}
            container
            item
            spacing={2}
            sx={{
                mb: fields.length - 1 === index ? 0 : 2,
            }}
        >
            <Grid item lg={4} md={4} xs={12}>
                <SelectElement
                    control={control}
                    label="Phone Type"
                    name={`${name}[${index}].type`}
                    options={types}
                    required
                />
            </Grid>
            <Grid item lg={8} md={8} xs={12}>
                <RHFPhoneInput
                    control={control}
                    setValue={setValue}
                    countries={countries || []}
                    name={`${name}[${index}].phone`}
                    label="Phone number"
                    required
                    endAdornment={
                        <InputAdornment position="end" sx={{ mr: 1 }}>
                            {fields.length - 1 === index && (
                                <Button
                                    size="small"
                                    color="success"
                                    onClick={() => append(phone)}
                                >
                                    add
                                </Button>
                            )}
                            {index > 0 && (
                                <IconButton
                                    size="small"
                                    color="error"
                                    onClick={() => remove(index)}
                                >
                                    <Delete fontSize="small" />
                                </IconButton>
                            )}
                        </InputAdornment>
                    }
                />
            </Grid>
        </Grid>
    ));
};

const ClientEmails = ({ control, name }) => {
    const { fields, append, remove } = useFieldArray({
        control,
        name,
    });

    return fields.map((item, index) => (
        <Grid
            key={item.id}
            item
            container
            spacing={2}
            sx={{
                mb: fields.length - 1 === index ? 0 : 2,
            }}
        >
            <Grid item lg={4} md={4} xs={12}>
                <SelectElement
                    control={control}
                    label="Email Type"
                    name={`${name}[${index}].type`}
                    options={types}
                    required
                />
            </Grid>
            <Grid item lg={8} md={8} xs={12}>
                <TextFieldElement
                    control={control}
                    name={`${name}[${index}].email`}
                    label="Email Address"
                    required
                    InputProps={{
                        endAdornment: (
                            <InputAdornment position="end">
                                <Stack direction="row" spacing={0.2}>
                                    {fields.length - 1 === index && (
                                        <Button
                                            size="small"
                                            color="success"
                                            onClick={() => append(email)}
                                        >
                                            add
                                        </Button>
                                    )}
                                    {index > 0 && (
                                        <IconButton
                                            size="small"
                                            color="error"
                                            onClick={() => remove(index)}
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
    ));
};
