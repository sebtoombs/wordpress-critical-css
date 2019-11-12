import React, {useEffect, useState} from 'react';

import styled from 'styled-components/macro';
import tw from 'tailwind.macro';

import AdminAjax from './utils/AdminAjax'

import ApiKey from './components/ApiKey'
import Status from './components/Status'
import Settings from './components/Settings'
import Separator from './components/Separator'
import Button from './components/Button'

const AppStyled = styled.div`
p { ${tw`m-0 mb-2`} }`

const Admin = props => {

    const [settingsState, setSettingsState] = useState({})


    /*useEffect(() => {

        AdminAjax('get_options').then(data=>{
            console.log('Options: ', data)
            setSettingsState(data)
        }).catch(err=>{
            setSettingsState(false)
        })

    }, [])*/

    const resetOptions = (e) => {
        AdminAjax('delete_options')
    }



    return <AppStyled>
        <ApiKey/>
        <Separator/>
        <Settings/>
        <Separator/>
        <Status/>
        <Separator/>
        <Button danger onClick={resetOptions}>Reset Options</Button>
        </AppStyled>
}
export default Admin