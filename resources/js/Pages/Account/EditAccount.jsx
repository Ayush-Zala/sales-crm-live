import { yupResolver } from "@hookform/resolvers/yup";
import { Head } from "@inertiajs/react";
import { Delete } from "@mui/icons-material";
import { LoadingButton } from "@mui/lab";
import {
    Box,
    Button,
    Grid,
    IconButton,
    InputAdornment,
    Stack,
} from "@mui/material";
import { useEffect, useState } from "react";
import {
    AutocompleteElement,
    FormContainer,
    RadioButtonGroup,
    SelectElement,
    TextFieldElement,
    useFieldArray,
    useForm,
} from "react-hook-form-mui";
import toast from "react-hot-toast";
import * as Yup from "yup";

import { timeZone, types, vendorTypes } from "@/Constant/constants";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { hasPermission } from "@/utils/AccessManager";
import ClientsTable from "./ClientsTable";
import DispositionsTable from "./DispositionsTable";

const schema = Yup.object().shape({
    companyid: Yup.number(),
    companyName: Yup.string(),
    faxNo: Yup.string(),
    website: Yup.string(),
    industry: Yup.object().shape({
        id: Yup.number(),
        name: Yup.string(),
    }),
    source: Yup.string(),
    companyPhone: Yup.array().of(
        Yup.object().shape({
            type: Yup.string(),
            phone: Yup.string(),
        })
    ),
    companyEmail: Yup.array().of(
        Yup.object().shape({
            type: Yup.string(),
            email: Yup.string(),
        })
    ),
    houseNo: Yup.string(),
    addressline1: Yup.string(),
    addressline2: Yup.string(),
    country: Yup.string().nullable(),
    state: Yup.string().nullable(),
    city: Yup.string().nullable(),
    zipcode: Yup.string(),
    timezone: Yup.string(),
    vendorType: Yup.string(),
});

const EditAccount = ({ auth, company, countries, industries }) => {
    console.log(industries);
    const { data } = company;

    const hasViewDispositionPermission = hasPermission(
        auth,
        "Can View Company Dispositions"
    );

    const defaultValues = {
        companyid: data.id || "",
        companyName: data.name || "",
        faxNo: data.fax || "",
        website: data.website || "",
        industry: { name: data.industry } || null,
        source: data.source || "",
        companyPhone:
            data.phone.length > 0
                ? data.phone
                : [{ companyId: "", phoneId: "", type: "", phone: "" }],
        companyEmail:
            data.email.length > 0
                ? data.email
                : [{ companyId: "", emailId: "", type: "", email: "" }],
        houseNo: data.block || "",
        addressline1: data.addressline1 || "",
        addressline2: data.addressline2 || "",
        country: data.country || "",
        state: data.state || "",
        city: data.city || "",
        zipcode: data.zip || "",
        timezone: data.timezone || "",
        vendorType: data.vendor_type || "",
    };

    const {
        control,
        handleSubmit,
        watch,
        formState: { isSubmitting },
    } = useForm({
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

    const submit = (data) => {
        const payload = {
            ...data,
            industry: data.industry.name,
        };

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("account.update"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(payload),
        }).then(async (response) => {
            const res = await response.json();
            if (response.ok) {
                toast.success(res.message);
            } else {
                toast.error("Error updating account");
            }
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Edit Account" />
            <MainContentTemplate
                title="Edit accounts"
                subtitle="Edit Accounts details here"
                button="Go back"
                href={route("account.index")}
            >
                <Grid
                    item
                    xs={12}
                    sx={{ display: "flex", justifyContent: "flex-end", gap: 1 }}
                >
                    <FormContainer
                        onSuccess={handleSubmit(submit)}
                        defaultValues={defaultValues}
                    >
                        <Grid item container xs={12} spacing={2}>
                            <Grid item xs={12}>
                                <VendorDetailsComponent control={control} />
                            </Grid>
                            <Grid item xs={12}>
                                <CompanyInformationComponent
                                    control={control}
                                    industries={industries}
                                />
                            </Grid>
                            <Grid item xs={12} lg={6}>
                                <PhoneListComponent
                                    companyPhoneFields={companyPhoneFields}
                                    control={control}
                                    appendCompanyPhone={appendCompanyPhone}
                                    removeCompanyPhone={removeCompanyPhone}
                                    types={types}
                                />
                            </Grid>
                            <Grid item xs={12} lg={6}>
                                <EmailListComponent
                                    companyEmailFields={companyEmailFields}
                                    control={control}
                                    appendCompanyEmail={appendCompanyEmail}
                                    removeCompanyEmail={removeCompanyEmail}
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <AddressComponent
                                    countries={countries}
                                    control={control}
                                    watch={watch}
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <LoadingButton
                                    type="submit"
                                    // variant="outlined"
                                    variant="contained"
                                    sx={{ mt: 3 }}
                                    fullWidth
                                    loading={isSubmitting}
                                >
                                    submit
                                </LoadingButton>
                            </Grid>
                        </Grid>
                    </FormContainer>
                </Grid>
                <Grid item xs={12} mt={3}>
                    <ClientsTable
                        clients={data.clients}
                        companyId={data.id}
                        companyName={data.name}
                    />
                </Grid>
                <Grid item xs={12} mt={3}>
                    {hasViewDispositionPermission && (
                        <DispositionsTable
                            dispositions={data.disposition_history}
                        />
                    )}
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default EditAccount;

const CompanyInformationComponent = ({ control, industries }) => {
    return (
        <Grid item container columns={12} spacing={2}>
            <Grid item xs={12} md={2.4}>
                <TextFieldElement
                    control={control}
                    name="companyName"
                    label="Company Name"
                />
            </Grid>
            <Grid item xs={12} md={2.4}>
                <TextFieldElement
                    control={control}
                    name="faxNo"
                    label="Fax No"
                />
            </Grid>
            <Grid item xs={12} md={2.4}>
                <TextFieldElement
                    control={control}
                    name="website"
                    label="Website"
                />
            </Grid>
            <Grid item xs={12} md={2.4}>
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
            <Grid item xs={12} md={2.4}>
                <TextFieldElement
                    control={control}
                    name="source"
                    label="Source"
                />
            </Grid>
        </Grid>
    );
};

const VendorDetailsComponent = ({ control }) => {
    return (
        <RadioButtonGroup
            row
            required
            control={control}
            label="Vendor Type"
            name="vendorType"
            options={vendorTypes}
        />
    );
};

const PhoneListComponent = ({
    companyPhoneFields,
    control,
    appendCompanyPhone,
    removeCompanyPhone,
    types,
}) => {
    const handleDeletePhone = (index) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("account.deletephone"), {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                phoneId: companyPhoneFields[index].phoneId,
                companyId: companyPhoneFields[index].companyId,
            }),
        })
            .then(async (response) => {
                const res = await response.json();
                if (response.ok) {
                    toast.success(res.message);
                    removeCompanyPhone(index);
                } else {
                    toast.error(res.error);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                toast.error("Error deleting phone");
            });
    };

    return companyPhoneFields.map((field, index) => (
        <Grid
            key={field.id}
            item
            container
            columns={12}
            spacing={1.5}
            mt={index > 0 && 1}
        >
            <Grid item xs={12} md={4}>
                <SelectElement
                    control={control}
                    label="Phone Type"
                    name={`companyPhone[${index}].type`}
                    options={types}
                />
            </Grid>
            <Grid item xs={12} md={8}>
                <TextFieldElement
                    control={control}
                    name={`companyPhone[${index}].phone`}
                    label="Phone"
                    InputProps={{
                        endAdornment: (
                            <InputAdornment position="end">
                                <Stack direction="row">
                                    {companyPhoneFields.length - 1 == index && (
                                        <Button
                                            color="success"
                                            size="small"
                                            onClick={() =>
                                                appendCompanyPhone(
                                                    "companyPhone"
                                                )
                                            }
                                        >
                                            add
                                        </Button>
                                    )}
                                    <IconButton
                                        size="small"
                                        color="error"
                                        onClick={() => handleDeletePhone(index)}
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
    ));
};

const EmailListComponent = ({
    companyEmailFields,
    control,
    appendCompanyEmail,
    removeCompanyEmail,
}) => {
    const handleDeleteEmail = (index) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("account.deleteemail"), {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                emailId: companyEmailFields[index].emailId,
                companyId: companyEmailFields[index].companyId,
            }),
        })
            .then(async (response) => {
                const res = await response.json();
                if (response.ok) {
                    toast.success(res.message);
                    removeCompanyEmail(index);
                } else {
                    toast.error(res.error);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                toast.error("Error deleting email");
            });
    };

    return companyEmailFields.map((field, index) => (
        <Grid
            item
            container
            key={field.id}
            columns={12}
            spacing={1.5}
            mt={index > 0 && 1}
        >
            <Grid item xs={12} md={4}>
                <SelectElement
                    control={control}
                    label="Email Type"
                    name={`companyEmail[${index}].type`}
                    options={types}
                />
            </Grid>
            <Grid item xs={12} md={8}>
                <TextFieldElement
                    type="email"
                    control={control}
                    name={`companyEmail[${index}].email`}
                    label="Email"
                    InputProps={{
                        endAdornment: (
                            <InputAdornment position="end">
                                <Stack direction="row">
                                    {companyEmailFields.length - 1 == index && (
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
                                    )}

                                    <IconButton
                                        size="small"
                                        color="error"
                                        onClick={() => handleDeleteEmail(index)}
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
    ));
};

const AddressComponent = ({ countries = [], control, watch }) => {
    const [states, setStates] = useState([]);
    const [cities, setCities] = useState([]);

    const countryValue = watch("country");
    const stateValue = watch("state");
    const cityValue = watch("city");

    // Map countries to options
    const countriesOptions = countries.map((country) => ({
        value: country.id,
        label: `${country.name} (${country.iso2})`,
    }));

    // Initialize form values when details are provided
    // useEffect(() => {
    //     if (details) {
    //         const companyDetails = details.companyInfo || {};

    //         setValue("country", companyDetails.country || "");
    //         setValue("state", companyDetails.state || "");
    //         setValue("city", companyDetails.cityName || "");
    //         setValue("zipcode", companyDetails.zipcode || "");
    //     }

    //     return () => reset(details);
    // }, [details, setValue, reset]);

    // Fetch states when country changes
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
                    setCities([]); // Reset cities when country changes
                });
        }
    }, [countryValue]);

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
    }, [stateValue]);

    return (
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
                    name="addressline1"
                    label="Address Line 1"
                />
            </Grid>
            <Grid item xs={12} md={4}>
                <TextFieldElement
                    control={control}
                    name="addressline2"
                    label="Address Line 2"
                />
            </Grid>
            <Grid item xs={12} md={2.4}>
                <SelectElement
                    control={control}
                    name="country"
                    label="Country"
                    options={countriesOptions}
                    labelKey="label"
                    valueKey="value"
                    defaultChecked={countryValue}
                />
            </Grid>
            <Grid item xs={12} md={2.4}>
                <SelectElement
                    control={control}
                    name="state"
                    label="State"
                    options={states}
                    labelKey="label"
                    valueKey="value"
                    defaultValue={stateValue}
                />
            </Grid>
            <Grid item xs={12} md={2.4}>
                <SelectElement
                    control={control}
                    name="city"
                    label="City"
                    options={cities}
                    labelKey="label"
                    valueKey="value"
                    defaultValue={cityValue}
                />
            </Grid>
            <Grid item xs={12} md={2.4}>
                <TextFieldElement
                    control={control}
                    name="zipcode"
                    label="Zipcode"
                />
            </Grid>
            <Grid item xs={12} md={2.4}>
                <SelectElement
                    control={control}
                    name="timezone"
                    label="Timezone"
                    options={timeZone} // Ensure `timeZone` is provided as a prop
                    labelKey="label"
                    valueKey="id"
                />
            </Grid>
        </Grid>
    );
};
