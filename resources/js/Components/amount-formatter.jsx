import { DataTypeProvider } from '@devexpress/dx-react-grid'

export const AmountFormatter = (props) => {
  let amount = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })

  return <DataTypeProvider formatterComponent={({ value }) => amount.format(value)} {...props} />
}
