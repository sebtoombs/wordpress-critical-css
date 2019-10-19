import React, {useEffect} from 'react';

import styled from 'styled-components/macro';
import tw from 'tailwind.macro';

import simpleStore from './simpleStore'
import axios from 'axios'


const Heading = styled.span`${tw`block mb-3 text-lg`}`

const Admin = props => {

    useEffect(() => {
        const wpObject = simpleStore.get('wpObject')

        axios.post(ajaxurl, {
            action: 'critical_css_get_options',//wpObject.ajax_prefix + 'get_options',
            nonce: wpObject.nonce
        }).then(resp=>{
            console.log('Resp: ', resp)
        }).catch(err=>{
            console.log('Err: ', err)
        })

    }, [])

    return <>
        <Heading>Status</Heading>
        </>
}
export default Admin