import axios from "axios";
import simpleStore from '../simpleStore'
import qs from 'querystring'

const getNonce = ()=> {
    const wpObject = simpleStore.get('wpObject')
    return wpObject.nonce;
}

const setNonce = (nonce) => {
    let wpObject = simpleStore.get('wpObject')
    wpObject.nonce = nonce;
    simpleStore.set('wpObject', wpObject)
}

const AdminAjax = (action, payload) => {
    const wpObject = simpleStore.get('wpObject')

    const bodySetup = {
        action: wpObject.ajax_prefix + action,
        nonce: getNonce()
    }
    const requestBody = {...payload, ...bodySetup}

    const config = {
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    }

    return axios.post(ajaxurl, qs.stringify(requestBody), config).then(resp=>{
        if(resp.data.nonce) {
            setNonce(resp.data.nonce)
        }
        //console.log('Resp: ', resp)
        //resp.data { data {message}, }
        return resp.data.data;
    })
}
export default AdminAjax