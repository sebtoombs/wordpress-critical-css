import React, {useEffect, useState} from 'react'
import tw from "tailwind.macro";
import styled from 'styled-components/macro'
import AdminAjax from "../utils/AdminAjax";
import { toast } from 'react-toastify';

import Heading from './Heading'
import Button from './Button'
import FormGroup from './FormGroup'
import FormText from './FormText'
import Label from './Label'
import Input from './Input'
import TextArea from './TextArea'
import Switch from "react-switch";

const Settings = props => {

    const [settingsState, setSettingsState] = useState(null)

    useEffect(() => {

        AdminAjax('get_options').then(data=>{
            console.log('Options: ', data)

            if(typeof data.ignore_styles) {
                data.ignore_styles = data.ignore_styles.join("\r\n")
            }

            setSettingsState(data)
        }).catch(err=>{
            setSettingsState(false)
        })

    }, [])

    const updateSetting = (name, value) => {
        let newValue = {...settingsState}
        newValue[name] = value
        setSettingsState(newValue)
    }

    const handleChange = e => {
        updateSetting(e.target.name, e.target.value)
    }
    const handleSwitch = name => {
        return value => {
            updateSetting(name, value)
        }
    }

    const renderSetting = (key) => {
        if(settingsState === null) return <p>Loading...</p>
        if(settingsState === false) return <p css={tw`text-red`}>Failed to load settings!</p>

        if(key === 'use_stale' || key === 'use_uncritical')
            return <Switch onChange={handleSwitch(key)} checked={settingsState[key]} />

        if(key === 'ignore_styles')
            return <TextArea value={settingsState[key]} onChange={handleChange} name={key}/>

        return <>
            <Input type="text" value={settingsState[key]} onChange={handleChange} name={key}/>
        </>
    }

    const saveSettings = () => {
        AdminAjax('update_option', {options: JSON.stringify(settingsState)}).then(data => {
            toast.success('Saved')
        }).catch(err=>{
            console.error(err)
            toast.error('Error saving settings')
        })
    }

    return <>
        <Heading>Settings</Heading>
        <FormGroup>
            <Label>Cache Time</Label>
            <FormText help>Set the time in seconds to keep critical css in cache. Set to zero to cache infinitely.</FormText>
            {renderSetting('cache_time')}
        </FormGroup>
        <FormGroup>
            <Label>Use stale</Label>
            <FormText help>Use stale critical css while new styles are being fetched.</FormText>
            {renderSetting('use_stale')}
        </FormGroup>
        <FormGroup>
            <Label>Use uncritical</Label>
            <FormText help>Use uncritical bundle.</FormText>
            {renderSetting('use_uncritical')}
        </FormGroup>
        <FormGroup>
            <Label>Ignore Stylesheets</Label>
            <FormText help>If the critical bundle misses some styles, or has conflicts, use this to ignore specific stylesheets from critical. One per line, eg /wp-content/twentynineteen/style.css</FormText>
            {renderSetting('ignore_styles')}
        </FormGroup>
        <div>
            <Button primary onClick={saveSettings}>Save Settings</Button>
        </div>
    </>
}
export default Settings