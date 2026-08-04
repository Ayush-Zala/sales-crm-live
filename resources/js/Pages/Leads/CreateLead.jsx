import { yupResolver } from "@hookform/resolvers/yup";
import { Head } from "@inertiajs/react";
import { Delete } from "@mui/icons-material";
import {
    Box,
    Button,
    Divider,
    Grid,
    IconButton,
    InputAdornment,
    Stack,
    Typography,
} from "@mui/material";
import { Fragment, useEffect, useState } from "react";
import {
    AutocompleteElement,
    DatePickerElement,
    FormContainer,
    SelectElement,
    TextFieldElement,
    useFieldArray,
    useForm,
} from "react-hook-form-mui";
import * as Yup from "yup";

import { timeZone, types } from "@/Constant/constants";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import toast from "react-hot-toast";

const defaultValues = {
    leadName: "",
    faxNo: "",
    website: "",
    industry: "Graphics",
    description: "",
    companyPhone: [{ type: "", phone: "" }],
    companyEmail: [{ type: "", email: "" }],
    client: [
        {
            fname: "",
            lname: "",
            designation: "",
            linkedin_url: "",
            clientPhone: [{ type: "", phone: "" }],
            clientEmail: [{ type: "", email: "" }],
        },
    ],
    houseNo: "",
    street: "",
    addressline2: "",
    country: "",
    state: "",
    city: "",
    zipcode: "",
    timezone: "",
    leadStatus: "new",
    leadSource: "",
    companyType: "fixed",
    businessType: "",
    opportunityAmount: "",
    followupDate: "",
};

const schema = Yup.object().shape({
    leadName: Yup.string().required("Company Name is required"),
    faxNo: Yup.string(),
    website: Yup.string(),
    industry: Yup.string().required("Industry is required"),
    description: Yup.string(),
    companyPhone: Yup.array().of(
        Yup.object().shape({
            type: Yup.string(),
            phone: Yup.string().nullable(),
        })
    ),
    companyEmail: Yup.array().of(
        Yup.object().shape({
            type: Yup.string().required("Email Type is required"),
            email: Yup.string().email().required("Email is required"),
        })
    ),
    client: Yup.array().of(
        Yup.object().shape({
            designation: Yup.string(),
            fname: Yup.string(),
            lname: Yup.string(),
            linkedin_url: Yup.string(),
            clientPhone: Yup.array().of(
                Yup.object().shape({
                    type: Yup.string(),
                    phone: Yup.string().nullable(),
                })
            ),
            clientEmail: Yup.array().of(
                Yup.object().shape({
                    type: Yup.string(),
                    email: Yup.string().email(),
                })
            ),
        })
    ),
    houseNo: Yup.string(),
    street: Yup.string(),
    addressline2: Yup.string(),
    country: Yup.string(),
    state: Yup.string().nullable(),
    city: Yup.string().nullable(),
    zipcode: Yup.string(),
    timezone: Yup.string(),
    leadStatus: Yup.string().required("Lead Status is required"),
    leadSource: Yup.string(),
    companyType: Yup.string().required("Company Type is required"),
    businessType: Yup.string(),
    opportunityAmount: Yup.string(),
    followupDate: Yup.string().nullable(),
});

export default function CreateLead({ auth, countries, industries }) {
    const { control, handleSubmit, reset, watch } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    const {
        fields: companyPhoneFields,
        append: appendCompanyPhone,
        remove: removeCompanyPhone,
    } = useFieldArray({
        control,
        name: "companyPhone",
    });

    const {
        fields: companyEmailFields,
        append: appendCompanyEmail,
        remove: removeCompanyEmail,
    } = useFieldArray({
        control,
        name: "companyEmail",
    });

    const {
        fields: clientPhoneFields,
        append: appendClientPhone,
        remove: removeClientPhone,
    } = useFieldArray({
        control,
        name: `client.0.clientPhone`,
    });

    const {
        fields: clientEmailFields,
        append: appendClientEmail,
        remove: removeClientEmail,
    } = useFieldArray({
        control,
        name: `client.0.clientEmail`,
    });

    const {
        fields: ClientInformationFields,
        append: appendClientInformationPhone,
        remove: removeClientInformationPhone,
    } = useFieldArray({
        control,
        name: "client",
    });

    const submit = (data) => {
        const modifiedData = {
            ...data,
            companyPhone: data.companyPhone.map((phoneField) => {
                const countryCode = phoneField.countries?.phonecode; // Ensure country code exists
                const phoneNumber = phoneField.phone;
                const combinedPhone =
                    phoneNumber && `+${countryCode}${phoneNumber}`.trim();

                delete phoneField.countries;

                return {
                    ...phoneField,
                    phone: combinedPhone,
                };
            }),
            client: data.client.map((clientField) => {
                return {
                    ...clientField,
                    clientPhone:
                        clientField.clientPhone &&
                        clientField.clientPhone.map((phoneField) => {
                            const countryCode = phoneField.countries?.phonecode; // Ensure country code exists
                            const phoneNumber = phoneField.phone;
                            const combinedPhone =
                                phoneNumber &&
                                `+${countryCode}${phoneNumber}`.trim();
                            delete phoneField.countries;

                            return {
                                ...phoneField,
                                phone: combinedPhone,
                            };
                        }),
                };
            }),
        };

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("lead.store"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(modifiedData),
        })
            .then((response) => response.json())
            .then((data) => {
                toast.success(data.message);
                reset(defaultValues);
            })
            .catch((error) => {
                toast.error(data.error);
            });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Create Lead" />
            <MainContentTemplate
                title="Create Lead"
                subtitle="Create Lead from here"
                button="Go back"
                href={route("lead.index")}
            >
                <Grid item xs={12}>
                    <FormContainer
                        onSuccess={handleSubmit(submit)}
                        defaultValues={defaultValues}
                    >
                        <Grid item container xs={12} spacing={2}>
                            <Grid item xs={12}>
                                <CompanyInformationComponent
                                    control={control}
                                    industries={industries}
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <PhoneListComponent
                                    companyPhoneFields={companyPhoneFields}
                                    countries={countries}
                                    control={control}
                                    appendCompanyPhone={appendCompanyPhone}
                                    removeCompanyPhone={removeCompanyPhone}
                                    watch={watch}
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <EmailListComponent
                                    companyEmailFields={companyEmailFields}
                                    control={control}
                                    appendCompanyEmail={appendCompanyEmail}
                                    removeCompanyEmail={removeCompanyEmail}
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <ClientInformationComponent
                                    control={control}
                                    ClientInformationFields={
                                        ClientInformationFields
                                    }
                                    appendClientInformationPhone={
                                        appendClientInformationPhone
                                    }
                                    removeClientInformationPhone={
                                        removeClientInformationPhone
                                    }
                                    clientPhoneFields={clientPhoneFields}
                                    appendClientPhone={appendClientPhone}
                                    removeClientPhone={removeClientPhone}
                                    clientEmailFields={clientEmailFields}
                                    appendClientEmail={appendClientEmail}
                                    removeClientEmail={removeClientEmail}
                                    countries={countries}
                                    watch={watch}
                                    timeZone={timeZone}
                                    types={types}
                                />
                            </Grid>

                            <Grid item xs={12}>
                                <AddressComponent
                                    control={control}
                                    countries={countries}
                                    watch={watch}
                                    timeZone={timeZone}
                                />
                            </Grid>

                            <Grid item xs={12}>
                                <DetailsComponent control={control} />
                            </Grid>
                        </Grid>
                        <Button
                            type="submit"
                            variant="contained"
                            sx={{ mt: 3 }}
                        >
                            Submit
                        </Button>
                    </FormContainer>
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}

const CompanyInformationComponent = ({ control, industries }) => {
    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Company Information</Typography>
            </Grid>
            <Grid item container columns={12} spacing={2}>
                <Grid item xs={12} md={3}>
                    <TextFieldElement
                        control={control}
                        name="leadName"
                        label="Company Name"
                        required
                    />
                </Grid>
                <Grid item xs={12} md={3}>
                    <TextFieldElement
                        control={control}
                        name="faxNo"
                        label="Fax No"
                    />
                </Grid>
                <Grid item xs={12} md={3}>
                    <TextFieldElement
                        control={control}
                        name="website"
                        label="Website"
                    />
                </Grid>
                <Grid item xs={12} md={3}>
                    <SelectElement
                        control={control}
                        name="industry"
                        label="Industry"
                        options={industries}
                        labelKey="industry"
                        valueKey="industry"
                    />
                </Grid>
                <Grid item xs={12} md={12}>
                    <TextFieldElement
                        control={control}
                        name="description"
                        label="Description"
                        multiline
                        rows={3}
                        fullWidth
                    />
                </Grid>
            </Grid>
        </Fragment>
    );
};

const PhoneListComponent = ({
    companyPhoneFields,
    control,
    appendCompanyPhone,
    removeCompanyPhone,
    countries,
}) => {
    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Phone</Typography>
            </Grid>
            <Grid item container xs={12} md={10} spacing={1}>
                {companyPhoneFields.map((field, index) => (
                    <Grid
                        key={field.id}
                        item
                        container
                        columns={12}
                        spacing={1.5}
                    >
                        <Grid item xs={12} md={2}>
                            <SelectElement
                                control={control}
                                label="Phone Type"
                                name={`companyPhone[${index}].type`}
                                options={types}
                            />
                        </Grid>
                        <Grid item xs={12} md={4}>
                            <SelectElementCountries
                                control={control}
                                countries={countries}
                                name={`companyPhone[${index}].countries`}
                            />
                        </Grid>
                        <Grid item xs={12} md={4}>
                            <TextFieldElement
                                control={control}
                                name={`companyPhone[${index}].phone`}
                                label="Phone"
                                InputProps={{
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            <Stack direction="row">
                                                <Button
                                                    color="success"
                                                    size="small"
                                                    onClick={() =>
                                                        appendCompanyPhone()
                                                    }
                                                >
                                                    add
                                                </Button>
                                                {index > 0 && (
                                                    <IconButton
                                                        size="small"
                                                        color="error"
                                                        onClick={() =>
                                                            removeCompanyPhone(
                                                                index
                                                            )
                                                        }
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

const SelectElementCountries = ({ control, countries, name }) => {
    // const countriesObj = countries
    //     ? countries.map((country) => ({
    //           ...country,
    //           id: `${country.phonecode} ${country.name}`, // Unique identifier with phone code and country name
    //           label: (
    //           ),
    //       }))
    //     : [];

    return (
        <AutocompleteElement
            control={control}
            name={name}
            label="Country code"
            options={countries}
            autocompleteProps={{
                renderOption: (props, option) => {
                    const { key, ...optionProps } = props;
                    return (
                        <Box
                            key={key}
                            component="li"
                            sx={{
                                "& > img": { mr: 2, flexShrink: 0 },
                                "& > span": { fontWeight: 500 },
                            }}
                            {...optionProps}
                        >
                            <img
                                loading="lazy"
                                width="20"
                                srcSet={`https://flagcdn.com/w40/${option.iso2.toLowerCase()}.png 2x`}
                                src={`https://flagcdn.com/w20/${option.iso2.toLowerCase()}.png`}
                                alt=""
                            />
                            <span>{`${option.name}(${option.iso2}) +${option.phonecode} `}</span>
                        </Box>
                    );
                },
                getOptionLabel: (option) =>
                    `+${option.phonecode} - ${option.name}`,
            }}
        />
    );
};

const EmailListComponent = ({
    companyEmailFields,
    control,
    appendCompanyEmail,
    removeCompanyEmail,
}) => {
    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Email</Typography>
            </Grid>
            <Grid item container xs={12} md={6} spacing={1}>
                {companyEmailFields.map((field, index) => (
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
                                name={`companyEmail[${index}].type`}
                                options={types}
                                required
                            />
                        </Grid>
                        <Grid item xs={12} md={8}>
                            <TextFieldElement
                                type="email"
                                control={control}
                                name={`companyEmail[${index}].email`}
                                label="Email"
                                required
                                InputProps={{
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            <Stack direction="row">
                                                <Button
                                                    color="success"
                                                    size="small"
                                                    onClick={() =>
                                                        appendCompanyEmail(
                                                            "companyEmail"
                                                        )
                                                    }
                                                >
                                                    add
                                                </Button>
                                                {index > 0 && (
                                                    <IconButton
                                                        size="small"
                                                        color="error"
                                                        onClick={() =>
                                                            removeCompanyEmail(
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

const ClientInformationComponent = ({
    control,
    ClientInformationFields,
    appendClientInformationPhone,
    removeClientInformationPhone,
    clientPhoneFields,
    appendClientPhone,
    removeClientPhone,
    clientEmailFields,
    appendClientEmail,
    removeClientEmail,
    countries,
    types,
}) => {
    // const options = [
    //     { label: "Mrs", value: "mrs" },
    //     { label: "Mis", value: "mis" },
    //     { label: "Other", value: "Other" },
    // ];

    return (
        <Fragment>
            <Grid container spacing={2}>
                <Grid item xs={12}>
                    <Divider sx={{ bgcolor: "#c7c7c7" }} />
                </Grid>
                <Grid item xs={12}>
                    <Typography variant="h6">Client Information</Typography>
                </Grid>
                {ClientInformationFields.map((field, index) => (
                    <Fragment key={index}>
                        <Grid item xs={12}>
                            <Divider sx={{ bgcolor: "#c7c7c7" }} />
                        </Grid>
                        <Grid item container key={field.id} spacing={2.5}>
                            <Grid item xs={12} md={3}>
                                <TextFieldElement
                                    control={control}
                                    name={`client[${index}].fname`}
                                    label="First Name"
                                />
                            </Grid>
                            <Grid item xs={12} md={3}>
                                <TextFieldElement
                                    control={control}
                                    name={`client[${index}].lname`}
                                    label="Last Name"
                                />
                            </Grid>
                            <Grid item xs={12} md={3}>
                                <TextFieldElement
                                    control={control}
                                    name={`client[${index}].designation`}
                                    label="Designation"
                                />
                            </Grid>
                            <Grid item xs={12} md={3}>
                                <TextFieldElement
                                    control={control}
                                    name={`client[${index}].linkedin_url`}
                                    label="LinkedIn URL"
                                />
                            </Grid>

                            <Grid item xs={12} md={12}>
                                <PhoneClientListComponent
                                    control={control}
                                    countries={countries}
                                    clientPhoneFields={clientPhoneFields}
                                    appendClientPhone={appendClientPhone}
                                    removeClientPhone={removeClientPhone}
                                    types={types}
                                />
                            </Grid>

                            <Grid item xs={12} md={12}>
                                <EmailClientListComponent
                                    control={control}
                                    clientEmailFields={clientEmailFields}
                                    appendClientEmail={appendClientEmail}
                                    removeClientEmail={removeClientEmail}
                                    types={types}
                                />
                            </Grid>

                            <Grid
                                item
                                xs={12}
                                style={{
                                    display: "flex",
                                    justifyContent: "flex-end",
                                    marginBottom: "10px",
                                }}
                            >
                                <InputAdornment position="end">
                                    <Stack direction="row">
                                        <Button
                                            color="success"
                                            size="small"
                                            variant="contained"
                                            onClick={() =>
                                                appendClientInformationPhone()
                                            }
                                        >
                                            Add
                                        </Button>
                                        {index > 0 && (
                                            <IconButton
                                                size="small"
                                                color="error"
                                                onClick={() =>
                                                    removeClientInformationPhone(
                                                        index
                                                    )
                                                }
                                            >
                                                <Delete fontSize="small" />
                                            </IconButton>
                                        )}
                                    </Stack>
                                </InputAdornment>
                            </Grid>
                        </Grid>
                    </Fragment>
                ))}
                <Grid item xs={12}>
                    <Divider sx={{ bgcolor: "#c7c7c7" }} />
                </Grid>
            </Grid>
        </Fragment>
    );
};

const EmailClientListComponent = ({
    clientEmailFields,
    control,
    appendClientEmail,
    removeClientEmail,
}) => {
    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Client Email</Typography>
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
                                name={`client[${index}].clientEmail[${index}].type`}
                                options={types}
                            />
                        </Grid>
                        <Grid item xs={12} md={8}>
                            <TextFieldElement
                                control={control}
                                type="email"
                                name={`client[${index}].clientEmail[${index}].email`}
                                label="Email"
                                InputProps={{
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            <Stack direction="row">
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

const PhoneClientListComponent = ({
    clientPhoneFields,
    control,
    appendClientPhone,
    removeClientPhone,
    countries,
}) => {
    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Client Phone</Typography>
            </Grid>
            <Grid item container xs={12} md={10} spacing={1}>
                {clientPhoneFields.map((field, index) => (
                    <Grid
                        key={field.id}
                        item
                        container
                        columns={12}
                        spacing={1.5}
                    >
                        <Grid item xs={12} md={2}>
                            <SelectElement
                                control={control}
                                label="Phone Type"
                                name={`client[${index}].clientPhone[${index}].type`}
                                options={types}
                            />
                        </Grid>
                        <Grid item xs={12} md={4}>
                            <SelectElementCountries
                                control={control}
                                countries={countries}
                                name={`client[${index}].clientPhone[${index}].countries`}
                            />
                        </Grid>
                        <Grid item xs={12} md={4}>
                            <TextFieldElement
                                control={control}
                                name={`client[${index}].clientPhone[${index}].phone`}
                                label="Phone"
                                InputProps={{
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            <Stack direction="row">
                                                <Button
                                                    color="success"
                                                    size="small"
                                                    onClick={() =>
                                                        appendClientPhone()
                                                    }
                                                >
                                                    add
                                                </Button>
                                                {index > 0 && (
                                                    <IconButton
                                                        size="small"
                                                        color="error"
                                                        onClick={() =>
                                                            removeClientPhone(
                                                                index
                                                            )
                                                        }
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

const AddressComponent = ({ countries, control, watch, timeZone }) => {
    const [states, setStates] = useState([]);
    const [cities, setCities] = useState([]);

    const countriesOptions = countries.map((country) => ({
        ...country,
        value: country.id,
        label: `${country.name} (${country.iso2})`,
    }));

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

    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Address</Typography>
            </Grid>
            <Grid item container columns={12} spacing={2}>
                <Grid item xs={12} md={4}>
                    <TextFieldElement
                        control={control}
                        name="houseNo"
                        label="House No"
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <TextFieldElement
                        control={control}
                        name="street"
                        label="Street"
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <TextFieldElement
                        control={control}
                        name="addressline2"
                        label="Addrress Line 2"
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <SelectElement
                        control={control}
                        name="country"
                        label="Country"
                        options={countriesOptions}
                        labelKey="label"
                        valueKey="value"
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <SelectElement
                        control={control}
                        name="state"
                        label="State"
                        options={states}
                        labelKey="label"
                        valueKey="value"
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <SelectElement
                        control={control}
                        name="city"
                        label="City"
                        options={cities}
                        labelKey="label"
                        valueKey="value"
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <TextFieldElement
                        control={control}
                        name="zipcode"
                        label="Zipcode"
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <SelectElement
                        control={control}
                        name="timezone"
                        label="Timezone"
                        options={timeZone}
                        labelKey="label"
                        valueKey="id"
                    />
                </Grid>
            </Grid>
        </Fragment>
    );
};

const DetailsComponent = ({ control }) => {
    const options = [
        { label: "New", value: "new" },
        { label: "Assigned", value: "assigned" },
        { label: "In Process", value: "inprocess" },
        { label: "Converted", value: "converted" },
        { label: "Recycled", value: "recycled" },
        { label: "Dead", value: "dead" },
    ];

    const optionsleadSource = [
        { label: "Call", value: "call" },
        { label: "Email", value: "email" },
        { label: "Existing Customer", value: "existingCustomer" },
        { label: "Partner", value: "partner" },
        { label: "Public Relations", value: "publicRelatins" },
        { label: "WebSite", value: "website" },
        { label: "Campaign", value: "Campaign" },
        { label: "Other", value: "other" },
    ];

    const optionsCompoanyType = [
        { label: "Fixed", value: "fixed" },
        { label: "Retail", value: "retail" },
    ];

    return (
        <Fragment>
            <Grid item xs={12}>
                <Typography variant="h6">Details</Typography>
            </Grid>
            <Grid item container columns={12} spacing={2}>
                <Grid item xs={12} md={4}>
                    <SelectElement
                        control={control}
                        name="leadStatus"
                        label="Lead Status"
                        required
                        options={options}
                        labelKey="label"
                        valueKey="value"
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <SelectElement
                        control={control}
                        name="leadSource"
                        label="Lead Source"
                        options={optionsleadSource}
                        labelKey="label"
                        valueKey="value"
                    />
                </Grid>

                <Grid item xs={12} md={4}>
                    <SelectElement
                        control={control}
                        name="companyType"
                        label="Comapany Type"
                        options={optionsCompoanyType}
                        labelKey="label"
                        valueKey="value"
                        required
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <TextFieldElement
                        control={control}
                        name="businessType"
                        label="Business Type"
                    />
                </Grid>

                <Grid item xs={12} md={4}>
                    <TextFieldElement
                        control={control}
                        name="opportunityAmount"
                        label="Opportunity Amount"
                    />
                </Grid>
                <Grid item xs={12} md={4}>
                    <DatePickerElement
                        control={control}
                        name="followupDate"
                        label="Followup Date"
                    />
                </Grid>
            </Grid>
        </Fragment>
    );
};
