import React, {useEffect, useState} from 'react'
import tw from "tailwind.macro";
import styled from 'styled-components/macro'
import AdminAjax from "../utils/AdminAjax";

import Heading from './Heading'

const Status = props => {

    const [statusState, setStatusState] = useState({})

    useEffect(() => {

        AdminAjax('get_status').then(data=>{
            console.log('Status: ', data)
            setStatusState(data)
        }).catch(err=>{
            setStatusState(false)
        })

    }, [])

    const renderCacheDirStatus = () => {
        if(!statusState.cache_dir) return 'Loading...'
        if(!statusState.cache_dir[0]) {
            if(statusState.cache_dir[1] === 'write') {
                return <p css={tw`text-red-500`}>Exists, not writable.</p>
            }
            if(statusState.cache_dir[1] === 'exist') {
                return <p css={tw`text-red-500`}>Does not exist</p>
            }
            return <p css={tw`text-red-500`}>Unknown error</p>
        }
        return <p css={tw`text-green-500`}>Exists, Writable</p>

    }

    const renderApiStatus = () => {
        if(!statusState.api_status) return 'Loading...'
        if(!statusState.api_status[0]) {
            return <p css={tw`text-red-500`}>Unreachable</p>
        }
        return <p css={tw`text-green-500`}>Ping OK</p>
    }

    return <>
        <Heading>Status</Heading>
        {statusState === false ? <p>Error loading status.</p> :
            Object.keys(statusState).length ?
                    <table>
                        <tbody>
                        <tr>
                            <td><p>Cache Directory:</p></td>
                            <td>{renderCacheDirStatus()}</td>
                        </tr>
                        <tr>
                            <td><p>API:</p></td>
                            <td>{renderApiStatus()}</td>
                        </tr>
                        </tbody>
                    </table>
                    : <p>Loading...</p>
        }
    </>
}
export default Status