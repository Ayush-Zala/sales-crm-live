import { DataTypeProvider } from '@devexpress/dx-react-grid'
import { format } from 'date-fns'

export const DataTimeFormatter = (props) => {
  return <DataTypeProvider formatterComponent={({ value }) => format(new Date(value), 'dd/MM/yyyy hh:mm:ss a')} {...props} />
}

export const DataFormatter = (props) => {
  return <DataTypeProvider formatterComponent={({ value }) => format(new Date(value), 'dd/MM/yyyy')} {...props} />
}
