import React, {useEffect, useState} from 'react'
import tw from "tailwind.macro";
import styled from 'styled-components/macro'
import AdminAjax from "../utils/AdminAjax";
import { toast } from 'react-toastify';
import simpleStore from '../simpleStore'

import Heading from './Heading'
import Button from './Button'
import FormGroup from './FormGroup'
import FormText from './FormText'
import Label from './Label'
import Input from './Input'

const ApiKey = props => {
    const wpObject = simpleStore.get('wpObject')
    const initialHasKey = wpObject.has_api_key

    const [apiKey, setApiKey] = useState("")
    const [hasKey, setHasKey] = useState(initialHasKey)

    useEffect(() => {
        if(hasKey) {
            setApiKey('*******-*******-*******-*******')
        }
    }, [])

    const handleChange = e => {
        setApiKey(e.target.value)
    }

    const validateKey = () => {
        AdminAjax('validate_key', {key:apiKey}).then(data => {
            toast.success('Key validated')
            setHasKey(true)
            setApiKey('*******-*******-*******-*******')
        }).catch(err=>{
            console.error(err)
            toast.error('Error validating key')
        })
    }

    const revokeKey = () => {
        AdminAjax('revoke_key').then(data => {
            toast.success('Key revoked')
            setApiKey('')
            setHasKey(false)
        }).catch(err=>{
            console.error(err)
            toast.error('Error revoking key')
        })
    }

    return <>
        <Heading>API Key</Heading>
        <FormGroup>
            <Label>API Key</Label>
            <Input value={apiKey} onChange={handleChange} disabled={hasKey}/>
            {!hasKey ?
                <Button onClick={validateKey} disabled={!apiKey}>Validate</Button> : <Button danger onClick={revokeKey}>Revoke</Button>}
        </FormGroup>
        </>
}

export default ApiKey